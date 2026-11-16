<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleTransaction;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $todaySales = SaleTransaction::whereDate('sold_at', $today);
        $legacySales = Sale::whereNull('sale_transaction_id')->whereDate('sold_at', $today);

        $dailyStart = now()->startOfDay()->subDays(6);
        $dailyEnd = now()->endOfDay();
        $dailyTotals = [];

        SaleTransaction::whereBetween('sold_at', [$dailyStart, $dailyEnd])
            ->get(['sold_at', 'subtotal'])
            ->each(function ($sale) use (&$dailyTotals) {
                $key = $sale->sold_at->toDateString();
                $dailyTotals[$key] = ($dailyTotals[$key] ?? 0) + (float) $sale->subtotal;
            });
        Sale::whereNull('sale_transaction_id')
            ->whereBetween('sold_at', [$dailyStart, $dailyEnd])
            ->get(['sold_at', 'total'])
            ->each(function ($sale) use (&$dailyTotals) {
                $key = $sale->sold_at->toDateString();
                $dailyTotals[$key] = ($dailyTotals[$key] ?? 0) + (float) $sale->total;
            });

        $dailyLabels = [];
        $dailyValues = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $date = $dailyStart->copy()->addDays($offset);
            $dailyLabels[] = $date->locale('id')->translatedFormat('D');
            $dailyValues[] = $dailyTotals[$date->toDateString()] ?? 0;
        }

        $yearStart = now()->startOfYear();
        $monthlyTotals = [];
        SaleTransaction::whereBetween('sold_at', [$yearStart, now()->endOfYear()])
            ->get(['sold_at', 'subtotal'])
            ->each(function ($sale) use (&$monthlyTotals) {
                $key = $sale->sold_at->format('Y-m');
                $monthlyTotals[$key] = ($monthlyTotals[$key] ?? 0) + (float) $sale->subtotal;
            });
        Sale::whereNull('sale_transaction_id')
            ->whereBetween('sold_at', [$yearStart, now()->endOfYear()])
            ->get(['sold_at', 'total'])
            ->each(function ($sale) use (&$monthlyTotals) {
                $key = $sale->sold_at->format('Y-m');
                $monthlyTotals[$key] = ($monthlyTotals[$key] ?? 0) + (float) $sale->total;
            });

        $monthlyLabels = [];
        $monthlyValues = [];
        for ($monthOffset = 0; $monthOffset < 12; $monthOffset++) {
            $month = $yearStart->copy()->addMonths($monthOffset);
            $monthlyLabels[] = $month->locale('id')->translatedFormat('M');
            $monthlyValues[] = $monthlyTotals[$month->format('Y-m')] ?? 0;
        }

        return view('dashboard.index', [
            'metrics' => [
                'medicine_types' => Medicine::count(),
                'stock_units' => (int) Medicine::sum('stock'),
                'today_transactions' => (clone $todaySales)->count() + (clone $legacySales)->count(),
                'today_revenue' => (float) (clone $todaySales)->sum('subtotal') + (float) (clone $legacySales)->sum('total'),
                'low_stock' => Medicine::whereColumn('stock', '<=', 'minimum_stock')->count(),
                'expired_medicines' => Medicine::whereDate('expires_at', '<', $today)->count(),
            ],
            'dailyChart' => $this->chartData($dailyLabels, $dailyValues),
            'monthlyChart' => $this->chartData($monthlyLabels, $monthlyValues),
            'recentTransactions' => SaleTransaction::withCount('items')->latest('sold_at')->limit(8)->get(),
        ]);
    }

    private function chartData(array $labels, array $values): array
    {
        $max = max($values ?: [0]);
        $lastIndex = max(count($values) - 1, 1);
        $points = [];

        foreach ($values as $index => $value) {
            $x = 44 + (370 * $index / $lastIndex);
            $y = 160 - ($max > 0 ? (130 * $value / $max) : 0);
            $points[] = round($x, 1).','.round($y, 1);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'points' => implode(' ', $points),
            'hasData' => $max > 0,
        ];
    }
}