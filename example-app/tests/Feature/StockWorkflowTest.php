<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleTransaction;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class StockWorkflowTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_manual_stock_addition_updates_inventory_and_history(): void
    {
        $medicine = $this->makeMedicine(['stock' => 4, 'minimum_stock' => 5]);

        $this->get(route('stock.index'))->assertOk();
        $this->post(route('stock.store'), [
            'medicine_id' => $medicine->id,
            'quantity' => 6,
            'note' => 'Restok dari gudang.',
        ])->assertRedirect(route('stock.index'));

        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 10]);
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'direction' => 'in',
            'source' => 'adjustment',
            'quantity' => 6,
            'stock_before' => 4,
            'stock_after' => 10,
        ]);
        $this->get(route('stock.history'))->assertOk()->assertSee('Restok dari gudang.');
    }

    public function test_sales_decrease_stock_and_insufficient_stock_is_rejected(): void
    {
        $medicine = $this->makeMedicine(['stock' => 10]);

        $this->get(route('sales.create'))->assertOk();
        $this->post(route('sales.store'), [
            'items' => [['medicine_id' => $medicine->id, 'quantity' => 3]],
            'paid_amount' => 4500,
        ])->assertRedirect();

        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 7]);
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'direction' => 'out',
            'source' => 'sale',
            'quantity' => 3,
            'stock_before' => 10,
            'stock_after' => 7,
        ]);

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                'items' => [['medicine_id' => $medicine->id, 'quantity' => 8]],
                'paid_amount' => 12000,
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 7]);
        $this->assertSame(1, Sale::count());
    }

    public function test_expired_medicines_cannot_be_sold(): void
    {
        $medicine = $this->makeMedicine(['stock' => 5, 'expires_at' => now()->subDay()->toDateString()]);

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                'items' => [['medicine_id' => $medicine->id, 'quantity' => 1]],
                'paid_amount' => 1500,
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('items.0.medicine_id');

        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 5]);
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, SaleTransaction::count());
    }

    public function test_purchase_only_adds_stock_once_confirmed(): void
    {
        $medicine = $this->makeMedicine(['stock' => 2]);
        $secondMedicine = $this->makeMedicine(['stock' => 5]);
        $supplier = Supplier::firstOrCreate(['code' => 'SUP-TEST'], [
            'name' => 'Pemasok Tes',
            'company_name' => 'PT Pemasok Tes',
            'address' => 'Jl. Tes No. 1',
            'phone' => '0215550101',
            'email' => 'supplier-workflow@example.test',
        ]);

        $this->get(route('purchases.create'))->assertOk();
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['medicine_id' => $medicine->id, 'quantity' => 8, 'unit_cost' => '2200.00'],
                ['medicine_id' => $secondMedicine->id, 'quantity' => 3, 'unit_cost' => '4000.00'],
            ],
            'note' => 'Faktur 0041',
        ])->assertRedirect(route('purchases.index'));

        $order = PurchaseOrder::with('items')->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame('29600.00', $order->total);
        $this->assertCount(2, $order->items);
        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 2]);
        $this->assertDatabaseHas('medicines', ['id' => $secondMedicine->id, 'stock' => 5]);
        $this->get(route('purchases.index'))->assertOk()->assertSee('Menunggu konfirmasi')->assertSee('29.600');

        $this->post(route('purchases.orders.confirm', $order))->assertRedirect(route('purchases.index'));
        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'stock' => 10,
            'purchase_price' => '2200.00',
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
        ]);
        $this->assertDatabaseHas('medicines', [
            'id' => $secondMedicine->id,
            'stock' => 8,
            'purchase_price' => '4000.00',
            'supplier_id' => $supplier->id,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'direction' => 'in',
            'source' => 'purchase',
            'quantity' => 8,
            'stock_before' => 2,
            'stock_after' => 10,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $secondMedicine->id,
            'direction' => 'in',
            'source' => 'purchase',
            'quantity' => 3,
            'stock_before' => 5,
            'stock_after' => 8,
        ]);

        $this->post(route('purchases.orders.confirm', $order))->assertStatus(409);
        $this->assertSame(2, StockMovement::where('source', 'purchase')->count());
    }

    public function test_purchase_confirmation_rolls_back_all_items_if_one_stock_would_overflow(): void
    {
        $firstMedicine = $this->makeMedicine(['stock' => 2]);
        $fullMedicine = $this->makeMedicine(['stock' => 4294967294]);
        $supplier = Supplier::firstOrCreate(['code' => 'SUP-TEST'], [
            'name' => 'Pemasok Tes',
            'company_name' => 'PT Pemasok Tes',
            'address' => 'Jl. Tes No. 1',
            'phone' => '0215550101',
            'email' => 'supplier-workflow@example.test',
        ]);
        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'items' => [
                ['medicine_id' => $firstMedicine->id, 'quantity' => 2, 'unit_cost' => 1000],
                ['medicine_id' => $fullMedicine->id, 'quantity' => 2, 'unit_cost' => 1000],
            ],
        ])->assertRedirect(route('purchases.index'));

        $order = PurchaseOrder::firstOrFail();
        $this->from(route('purchases.index'))
            ->post(route('purchases.orders.confirm', $order))
            ->assertRedirect(route('purchases.index'))
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('medicines', ['id' => $firstMedicine->id, 'stock' => 2]);
        $this->assertDatabaseHas('medicines', ['id' => $fullMedicine->id, 'stock' => 4294967294]);
        $this->assertDatabaseHas('purchase_orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertSame(0, StockMovement::where('source', 'purchase')->count());
    }

    public function test_low_stock_and_near_expiry_alerts_list_matching_medicines(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(12, 0));
        $lowStock = $this->makeMedicine([
            'code' => 'OBT-LOW',
            'name' => 'Stok Menipis',
            'stock' => 3,
            'minimum_stock' => 5,
            'expires_at' => now()->addDays(30)->toDateString(),
        ]);
        $this->makeMedicine([
            'code' => 'OBT-SAFE',
            'name' => 'Stok Aman',
            'stock' => 20,
            'minimum_stock' => 5,
            'expires_at' => now()->addDays(180)->toDateString(),
        ]);

        $this->get(route('stock.index'))->assertOk();
        $this->get(route('alerts.low-stock'))->assertOk()->assertSee($lowStock->name)->assertDontSee('Stok Aman');
        $this->get(route('alerts.expiring'))->assertOk()->assertSee($lowStock->name)->assertDontSee('Stok Aman');
    }

    public function test_initial_stock_is_recorded_as_a_movement(): void
    {
        $category = Category::create(['name' => 'Stok Awal']);
        $supplier = Supplier::create([
            'code' => 'SUP-OPEN',
            'name' => 'Pemasok Stok Awal',
            'company_name' => 'PT Pemasok Stok Awal',
            'address' => 'Jl. Tes No. 2',
            'phone' => '0215550102',
            'email' => 'pemasok-awal@example.test',
        ]);
        $this->post(route('medicines.store'), [
            'code' => 'OBT-OPEN',
            'name' => 'Obat Stok Awal',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock' => 12,
            'minimum_stock' => 4,
            'expires_at' => now()->addYear()->toDateString(),
            'supplier_name' => 'PT Mitra Sehat',
        ])->assertRedirect(route('medicines.index'));

        $medicine = Medicine::where('code', 'OBT-OPEN')->firstOrFail();
        $this->assertDatabaseHas('stock_movements', [
            'medicine_id' => $medicine->id,
            'source' => 'opening',
            'quantity' => 12,
            'stock_before' => 0,
            'stock_after' => 12,
        ]);
    }

    private function makeMedicine(array $overrides = []): Medicine
    {
        $category = Category::firstOrCreate(['name' => 'Persediaan']);
        $supplier = Supplier::firstOrCreate(['code' => 'SUP-TEST'], [
            'name' => 'Pemasok Tes',
            'company_name' => 'PT Pemasok Tes',
            'address' => 'Jl. Tes No. 1',
            'phone' => '0215550101',
            'email' => 'supplier-workflow@example.test',
        ]);

        return Medicine::create(array_merge([
            'code' => 'OBT-'.Str::upper(Str::random(8)),
            'name' => 'Obat Uji '.Str::random(5),
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1000,
            'sale_price' => 1500,
            'stock' => 10,
            'minimum_stock' => 5,
            'expires_at' => now()->addYear()->toDateString(),
            'supplier_name' => 'PT Mitra Sehat',
        ], $overrides));
    }
}