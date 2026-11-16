<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleTransaction;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTest extends AuthenticatedTestCase
{
    use RefreshDatabase;

    public function test_daily_weekly_and_monthly_sales_reports_respect_date_filters(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(12, 0));
        $this->makeSale('TRX-NOV-16', '2026-11-16 10:00:00', 25000);
        $this->makeSale('TRX-NOV-10', '2026-11-10 10:00:00', 12000);
        $this->makeSale('TRX-OCT-30', '2026-10-30 10:00:00', 9000);

        $daily = $this->get(route('reports.index', [
            'type' => 'sales_daily',
            'from' => '2026-11-16',
            'to' => '2026-11-16',
        ]))->assertOk()->assertSee('25.000');
        $daily->assertDontSee('12.000');

        $this->get(route('reports.index', [
            'type' => 'sales_weekly',
            'from' => '2026-11-09',
            'to' => '2026-11-16',
        ]))->assertOk()->assertSee('37.000');
        $this->get(route('reports.index', [
            'type' => 'sales_monthly',
            'from' => '2026-11-01',
            'to' => '2026-11-30',
        ]))->assertOk()->assertSee('37.000')->assertDontSee('9.000');
    }

    public function test_purchase_stock_and_expiry_reports_show_filtered_records(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(12, 0));
        [$medicine, $supplier] = $this->makeMedicineAndSupplier('OBT-RPT-1', 'Obat Laporan');
        $lowStockMedicine = $this->makeMedicineAndSupplier('OBT-RPT-LOW', 'Obat Stok Minimum')[0];
        $lowStockMedicine->update(['stock' => 1, 'minimum_stock' => 2]);
        $order = PurchaseOrder::create([
            'order_number' => 'PO-RPT-1',
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'total' => '10000.00',
            'status' => 'pending',
            'note' => null,
        ]);
        Purchase::create([
            'purchase_order_id' => $order->id,
            'purchase_number' => 'PO-RPT-1-001',
            'medicine_id' => $medicine->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'quantity' => 5,
            'unit_cost' => 2000,
            'status' => 'pending',
        ]);

        $this->get(route('reports.index', [
            'type' => 'purchases',
            'from' => '2026-11-16',
            'to' => '2026-11-16',
        ]))->assertOk()->assertSee('PO-RPT-1')->assertSee('10.000');
        $stockReport = $this->get(route('reports.index', ['type' => 'stock']))
            ->assertOk()->assertSee('Obat Laporan')->assertSee('Snapshot stok saat ini');
        $stockReport->assertViewHas('report', fn (array $report) => $report['summary'][2]['value'] === 1);
        $this->get(route('reports.index', [
            'type' => 'expiring',
            'from' => '2026-11-16',
            'to' => '2026-12-31',
        ]))->assertOk()->assertSee('Obat Laporan');
        $this->get(route('reports.index', [
            'type' => 'expiring',
            'from' => '2027-01-01',
            'to' => '2027-02-01',
        ]))->assertOk()->assertDontSee('Obat Laporan');
    }

    public function test_profit_report_uses_historical_cost_snapshot_and_notes_legacy_rows(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(12, 0));
        $medicine = $this->makeMedicineAndSupplier('OBT-PROFIT', 'Obat Profit')[0];
        $transaction = $this->makeSale('TRX-PROFIT', '2026-11-16 09:00:00', 5000);
        Sale::create([
            'sale_transaction_id' => $transaction->id,
            'sale_number' => 'TRX-PROFIT-001',
            'medicine_id' => $medicine->id,
            'quantity' => 2,
            'unit_price' => 2500,
            'unit_cost' => 1500,
            'total' => 5000,
            'sold_at' => $transaction->sold_at,
        ]);
        Sale::create([
            'sale_number' => 'LEGACY-PROFIT',
            'medicine_id' => $medicine->id,
            'quantity' => 1,
            'unit_price' => 2500,
            'total' => 2500,
            'sold_at' => $transaction->sold_at,
        ]);

        $this->get(route('reports.index', [
            'type' => 'profit',
            'from' => '2026-11-16',
            'to' => '2026-11-16',
        ]))->assertOk()
            ->assertSee('2.000')
            ->assertSee('1 baris transaksi lama');
    }

    public function test_pdf_and_xlsx_exports_return_real_file_formats(): void
    {
        $this->makeSale('TRX-EXPORT', now()->toDateTimeString(), 12500);
        $filters = ['type' => 'sales_daily', 'from' => now()->toDateString(), 'to' => now()->toDateString()];

        $pdf = $this->get(route('reports.export', ['format' => 'pdf'] + $filters))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $xlsx = $this->get(route('reports.export', ['format' => 'xlsx'] + $filters))->assertOk();
        $xlsx->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $xlsxContent = file_get_contents($xlsx->baseResponse->getFile()->getPathname());
        $this->assertStringStartsWith('PK', $xlsxContent);
    }

    private function makeSale(string $receipt, string $soldAt, int $total): SaleTransaction
    {
        return SaleTransaction::create([
            'receipt_number' => $receipt,
            'subtotal' => $total,
            'paid_amount' => $total,
            'change_amount' => 0,
            'sold_at' => $soldAt,
        ]);
    }

    private function makeMedicineAndSupplier(string $code, string $name): array
    {
        $category = Category::firstOrCreate(['name' => 'Kategori Laporan']);
        $supplier = Supplier::firstOrCreate(['code' => 'SUP-RPT'], [
            'name' => 'Pemasok Laporan',
            'company_name' => 'PT Pemasok Laporan',
            'address' => 'Jl. Laporan No. 1',
            'phone' => '0215550101',
            'email' => 'supplier-report@example.test',
        ]);
        $medicine = Medicine::create([
            'code' => $code,
            'name' => $name,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'Tablet',
            'unit' => 'Tablet',
            'purchase_price' => 1500,
            'sale_price' => 2500,
            'stock' => 9,
            'minimum_stock' => 2,
            'expires_at' => '2026-12-15',
            'supplier_name' => $supplier->name,
        ]);

        return [$medicine, $supplier];
    }
}