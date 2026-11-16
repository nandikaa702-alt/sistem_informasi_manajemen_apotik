<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    private const EXPIRY_WARNING_DAYS = 90;

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $medicines = Medicine::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $restockMedicines = Medicine::orderBy('name')->get(['id', 'code', 'name', 'stock']);

        $lowStockCount = Medicine::whereColumn('stock', '<=', 'minimum_stock')->count();
        $expiringCount = $this->expiringQuery()->count();

        return view('stock.index', compact('medicines', 'restockMedicines', 'search', 'lowStockCount', 'expiringCount'));
    }

    public function store(Request $request, StockService $stockService)
    {
        $data = $request->validate([
            'medicine_id' => ['required', 'integer', 'exists:medicines,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $stockService) {
            $medicine = Medicine::whereKey($data['medicine_id'])->lockForUpdate()->firstOrFail();
            $stockService->increaseLocked($medicine, (int) $data['quantity'], 'adjustment', $data['note'] ?? null);
        });

        return redirect()->route('stock.index')->with('status', 'Stok berhasil ditambahkan.');
    }

    public function history()
    {
        $movements = StockMovement::with(['medicine', 'sale', 'purchase'])
            ->latest()
            ->paginate(25);

        return view('stock.history', compact('movements'));
    }

    public function lowStock()
    {
        $medicines = Medicine::with('category')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->orderBy('stock')
            ->paginate(25);

        return view('stock.alerts', [
            'medicines' => $medicines,
            'title' => 'Stok Minimum',
            'description' => 'Obat dengan stok sama dengan atau di bawah batas minimum.',
            'alertType' => 'low',
        ]);
    }

    public function expiring()
    {
        $medicines = $this->expiringQuery()
            ->with('category')
            ->orderBy('expires_at')
            ->paginate(25);

        return view('stock.alerts', [
            'medicines' => $medicines,
            'title' => 'Mendekati Kedaluwarsa',
            'description' => 'Obat yang akan kedaluwarsa dalam '.self::EXPIRY_WARNING_DAYS.' hari ke depan.',
            'alertType' => 'expiring',
        ]);
    }

    private function expiringQuery()
    {
        return Medicine::whereBetween('expires_at', [
            now()->toDateString(),
            now()->addDays(self::EXPIRY_WARNING_DAYS)->toDateString(),
        ]);
    }
}