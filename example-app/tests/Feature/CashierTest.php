<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleTransaction;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashierTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_cashier_searches_available_medicines_by_name_or_code(): void
    {
        $paracetamol = $this->makeMedicine('OBT-PARA', 'Paracetamol', 1500, 10);
        $this->makeMedicine('OBT-IBU', 'Ibuprofen', 2500, 8);
        $this->makeMedicine('OBT-EMPTY', 'Stok Habis', 900, 0);
        $this->makeMedicine('OBT-OLD', 'Obat Kedaluwarsa', 500, 5, now()->subDay()->toDateString());

        $this->getJson(route('sales.medicine-search', ['q' => 'Paracetamol']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $paracetamol->id, 'code' => 'OBT-PARA', 'name' => 'Paracetamol']);
        $this->getJson(route('sales.medicine-search', ['q' => 'OBT-IBU']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['code' => 'OBT-IBU', 'name' => 'Ibuprofen']);
        $this->getJson(route('sales.medicine-search', ['q' => 'OBT-EMPTY']))->assertJsonCount(0);
        $this->getJson(route('sales.medicine-search', ['q' => 'OBT-OLD']))->assertJsonCount(0);
    }

    public function test_multi_item_checkout_calculates_payment_updates_stock_and_renders_receipt(): void
    {
        $paracetamol = $this->makeMedicine('OBT-PARA', 'Paracetamol', 1500, 10);
        $ibuprofen = $this->makeMedicine('OBT-IBU', 'Ibuprofen', 2500, 8);

        $this->get(route('sales.create'))->assertOk()->assertSee('Keranjang');
        $response = $this->post(route('sales.store'), [
            'items' => [
                ['medicine_id' => $paracetamol->id, 'quantity' => 2],
                ['medicine_id' => $ibuprofen->id, 'quantity' => 3],
            ],
            'paid_amount' => '20000.00',
        ]);

        $transaction = SaleTransaction::query()->firstOrFail();
        $response->assertRedirect(route('sales.receipt', $transaction));
        $this->assertSame('10500.00', $transaction->subtotal);
        $this->assertSame('20000.00', $transaction->paid_amount);
        $this->assertSame('9500.00', $transaction->change_amount);
        $this->assertSame(2, $transaction->items()->count());
        $this->assertDatabaseHas('medicines', ['id' => $paracetamol->id, 'stock' => 8]);
        $this->assertDatabaseHas('medicines', ['id' => $ibuprofen->id, 'stock' => 5]);
        $this->assertSame(2, StockMovement::where('source', 'sale')->count());

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee($transaction->receipt_number)
            ->assertSee('Lihat / cetak struk');
        $this->get(route('sales.receipt', $transaction))
            ->assertOk()
            ->assertSee('Paracetamol')
            ->assertSee('Ibuprofen')
            ->assertSee('9.500');
    }

    public function test_insufficient_payment_rolls_back_receipt_items_and_stock(): void
    {
        $medicine = $this->makeMedicine('OBT-ROLLBACK', 'Obat Uji Rollback', 2750, 6);

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                'items' => [['medicine_id' => $medicine->id, 'quantity' => 2]],
                'paid_amount' => '5000.00',
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('paid_amount');

        $this->assertSame(0, SaleTransaction::count());
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, StockMovement::where('source', 'sale')->count());
        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 6]);
    }

    public function test_duplicate_medicine_lines_are_rejected(): void
    {
        $medicine = $this->makeMedicine('OBT-DUP', 'Obat Uji Duplikat', 1000, 6);

        $this->from(route('sales.create'))
            ->post(route('sales.store'), [
                'items' => [
                    ['medicine_id' => $medicine->id, 'quantity' => 1],
                    ['medicine_id' => $medicine->id, 'quantity' => 1],
                ],
                'paid_amount' => 2000,
            ])
            ->assertRedirect(route('sales.create'))
            ->assertSessionHasErrors('items.1.medicine_id');
        $this->assertSame(0, SaleTransaction::count());
        $this->assertDatabaseHas('medicines', ['id' => $medicine->id, 'stock' => 6]);
    }

    private function makeMedicine(string $code, string $name, int $price, int $stock, ?string $expiresAt = null): Medicine
    {
        $category = Category::firstOrCreate(['name' => 'Kategori Kasir']);

        return Medicine::create([
            'code' => $code,
            'name' => $name,
            'category_id' => $category->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => $price,
            'sale_price' => $price,
            'stock' => $stock,
            'minimum_stock' => 2,
            'expires_at' => $expiresAt ?? now()->addYear()->toDateString(),
            'supplier_name' => 'Pemasok Tes',
        ]);
    }
}