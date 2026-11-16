<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const TYPES = [
        'sales_daily' => 'Penjualan Harian',
        'sales_weekly' => 'Penjualan Mingguan',
        'sales_monthly' => 'Penjualan Bulanan',
        'purchases' => 'Pembelian Obat',
        'stock' => 'Stok Obat',
        'expiring' => 'Obat Kedaluwarsa',
        'profit' => 'Keuntungan Penjualan',
    ];

    public function index(Request $request)
    {
        [$filters, $report] = $this->buildReport($request);

        return view('reports.index', [
            'filters' => $filters,
            'report' => $report,
            'types' => self::TYPES,
        ]);
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);
        [$filters, $report] = $this->buildReport($request);
        $filename = str_replace(' ', '-', strtolower($report['title'])).'-'.$filters['from'].'-'.$filters['to'];

        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', compact('filters', 'report'))
                ->setPaper('a4', 'landscape')
                ->download($filename.'.pdf');
        }

        return Excel::download(new ReportExport($report['headings'], $report['rows']), $filename.'.xlsx');
    }

    private function buildReport(Request $request): array
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $type = $filters['type'] ?? 'sales_daily';
        $today = now()->toDateString();
        $from = $filters['from'] ?? ($type === 'expiring' ? $today : now()->startOfMonth()->toDateString());
        $to = $filters['to'] ?? ($type === 'expiring' ? now()->addDays(90)->toDateString() : $today);
        $filters = compact('type', 'from', 'to');

        $report = match ($type) {
            'sales_daily', 'sales_weekly', 'sales_monthly' => $this->salesReport($type, $from, $to),
            'purchases' => $this->purchasesReport($from, $to),
            'stock' => $this->stockReport(),
            'expiring' => $this->expiringReport($from, $to),
            'profit' => $this->profitReport($from, $to),
        };
        $report['currency_columns'] = match ($type) {
            'sales_daily', 'sales_weekly', 'sales_monthly' => [2],
            'purchases' => [4],
            'profit' => [4, 5, 6],
            default => [],
        };

        return [$filters, $report];
    }

    private function salesReport(string $type, string $from, string $to): array
    {
        $groups = [];
        $addSale = function ($soldAt, int $revenueCents) use (&$groups, $type) {
            $date = Carbon::parse($soldAt);
            $key = match ($type) {
                'sales_weekly' => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'sales_monthly' => $date->format('Y-m'),
                default => $date->toDateString(),
            };
            $label = match ($type) {
                'sales_weekly' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('d/m/Y').' - '.$date->copy()->endOfWeek(Carbon::SUNDAY)->format('d/m/Y'),
                'sales_monthly' => $date->translatedFormat('F Y'),
                default => $date->format('d/m/Y'),
            };
            $groups[$key] ??= ['label' => $label, 'count' => 0, 'revenue_cents' => 0];
            $groups[$key]['count']++;
            $groups[$key]['revenue_cents'] += $revenueCents;
        };

        SaleTransaction::whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['sold_at', 'subtotal'])
            ->each(fn ($transaction) => $addSale($transaction->sold_at, $this->toCents($transaction->subtotal)));

        Sale::whereNull('sale_transaction_id')
            ->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['sold_at', 'total'])
            ->each(fn ($sale) => $addSale($sale->sold_at, $this->toCents($sale->total)));

        ksort($groups);
        $rows = [];
        $transactionCount = 0;
        $revenueCents = 0;
        foreach ($groups as $group) {
            $rows[] = [$group['label'], $group['count'], $this->centsToFloat($group['revenue_cents'])];
            $transactionCount += $group['count'];
            $revenueCents += $group['revenue_cents'];
        }

        return [
            'title' => self::TYPES[$type],
            'description' => 'Ringkasan transaksi berdasarkan periode.',
            'headings' => [$type === 'sales_daily' ? 'Tanggal' : 'Periode', 'Jumlah transaksi', 'Total penjualan (Rp)'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Jumlah transaksi', 'value' => $transactionCount, 'currency' => false],
                ['label' => 'Total penjualan', 'value' => $this->centsToFloat($revenueCents), 'currency' => true],
            ],
            'note' => null,
        ];
    }

    private function purchasesReport(string $from, string $to): array
    {
        $rows = [];
        $totalCents = 0;

        $orders = PurchaseOrder::withCount('items')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('created_at')
            ->get();
        foreach ($orders as $order) {
            $totalCents += $this->toCents($order->total);
            $rows[] = [
                $order->created_at->format('d/m/Y H:i'),
                $order->order_number,
                $order->supplier_name,
                $order->items_count,
                $this->decimalToFloat($order->total),
                $order->status === 'confirmed' ? 'Dikonfirmasi' : 'Menunggu konfirmasi',
            ];
        }

        $legacy = Purchase::whereNull('purchase_order_id')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->with('medicine')
            ->orderBy('created_at')
            ->get();
        foreach ($legacy as $purchase) {
            $lineCents = $this->toCents($purchase->unit_cost) * $purchase->quantity;
            $totalCents += $lineCents;
            $rows[] = [
                $purchase->created_at->format('d/m/Y H:i'),
                $purchase->purchase_number,
                $purchase->supplier_name,
                1,
                $this->centsToFloat($lineCents),
                $purchase->status === 'confirmed' ? 'Dikonfirmasi' : 'Menunggu konfirmasi',
            ];
        }

        return [
            'title' => self::TYPES['purchases'],
            'description' => 'Daftar pesanan pembelian dan status konfirmasi.',
            'headings' => ['Tanggal', 'No. pesanan', 'Pemasok', 'Jumlah item', 'Total (Rp)', 'Status'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Jumlah pesanan', 'value' => count($rows), 'currency' => false],
                ['label' => 'Total pembelian', 'value' => $this->centsToFloat($totalCents), 'currency' => true],
            ],
            'note' => null,
        ];
    }

    private function stockReport(): array
    {
        $medicines = Medicine::with(['category', 'supplier'])->orderBy('name')->get();
        $lowStockCount = $medicines->filter(fn (Medicine $medicine) => $medicine->stock <= $medicine->minimum_stock)->count();
        $rows = $medicines->map(fn (Medicine $medicine) => [
            $medicine->code,
            $medicine->name,
            $medicine->category->name,
            $medicine->stock,
            $medicine->minimum_stock,
            $medicine->unit,
            $medicine->expires_at->format('d/m/Y'),
            $medicine->supplier?->name ?? $medicine->supplier_name,
        ])->all();

        return [
            'title' => self::TYPES['stock'],
            'description' => 'Snapshot stok saat ini.',
            'headings' => ['Kode', 'Nama obat', 'Kategori', 'Stok', 'Stok minimum', 'Satuan', 'Kedaluwarsa', 'Pemasok'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Jenis obat', 'value' => $medicines->count(), 'currency' => false],
                ['label' => 'Total unit stok', 'value' => $medicines->sum('stock'), 'currency' => false],
                ['label' => 'Stok di bawah minimum', 'value' => $lowStockCount, 'currency' => false],
            ],
            'note' => 'Laporan ini menampilkan kondisi stok saat diekspor, bukan stok historis.',
        ];
    }

    private function expiringReport(string $from, string $to): array
    {
        $medicines = Medicine::with('category')
            ->whereBetween('expires_at', [$from, $to])
            ->orderBy('expires_at')
            ->get();
        $rows = $medicines->map(fn (Medicine $medicine) => [
            $medicine->code,
            $medicine->name,
            $medicine->category->name,
            $medicine->stock,
            $medicine->unit,
            $medicine->expires_at->format('d/m/Y'),
        ])->all();

        return [
            'title' => self::TYPES['expiring'],
            'description' => 'Obat dengan tanggal kedaluwarsa pada rentang yang dipilih.',
            'headings' => ['Kode', 'Nama obat', 'Kategori', 'Stok', 'Satuan', 'Tanggal kedaluwarsa'],
            'rows' => $rows,
            'summary' => [['label' => 'Jumlah obat', 'value' => $medicines->count(), 'currency' => false]],
            'note' => null,
        ];
    }

    private function profitReport(string $from, string $to): array
    {
        $sales = Sale::with('medicine')
            ->whereNotNull('unit_cost')
            ->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderBy('sold_at')
            ->get();
        $revenueCents = 0;
        $costCents = 0;
        $rows = [];

        foreach ($sales as $sale) {
            $lineRevenue = $this->toCents($sale->total);
            $lineCost = $this->toCents($sale->unit_cost) * $sale->quantity;
            $revenueCents += $lineRevenue;
            $costCents += $lineCost;
            $rows[] = [
                $sale->sold_at->format('d/m/Y H:i'),
                $sale->sale_number,
                $sale->medicine->name,
                $sale->quantity,
                $this->centsToFloat($lineRevenue),
                $this->centsToFloat($lineCost),
                $this->centsToFloat($lineRevenue - $lineCost),
            ];
        }

        $missingCostCount = Sale::whereNull('unit_cost')
            ->whereBetween('sold_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->count();

        return [
            'title' => self::TYPES['profit'],
            'description' => 'Keuntungan kotor dihitung dari total penjualan dikurangi snapshot harga modal saat transaksi.',
            'headings' => ['Tanggal', 'No. transaksi', 'Obat', 'Jumlah', 'Penjualan (Rp)', 'Modal (Rp)', 'Keuntungan (Rp)'],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total penjualan', 'value' => $this->centsToFloat($revenueCents), 'currency' => true],
                ['label' => 'Total modal', 'value' => $this->centsToFloat($costCents), 'currency' => true],
                ['label' => 'Keuntungan kotor', 'value' => $this->centsToFloat($revenueCents - $costCents), 'currency' => true],
            ],
            'note' => $missingCostCount > 0
                ? $missingCostCount.' baris transaksi lama tidak memiliki snapshot harga modal dan tidak disertakan dalam perhitungan keuntungan.'
                : null,
        ];
    }

    private function toCents($amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function centsToFloat(int $cents): float
    {
        return $cents / 100;
    }

    private function decimalToFloat($amount): float
    {
        return (float) $amount;
    }
}