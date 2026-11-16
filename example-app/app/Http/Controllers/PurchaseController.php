<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::withCount('items')
            ->with(['supplier', 'items.medicine'])
            ->latest()
            ->paginate(20);
        $legacyPurchases = Purchase::whereNull('purchase_order_id')
            ->with(['medicine', 'supplier'])
            ->latest()
            ->paginate(20, ['*'], 'legacy_page');

        return view('purchases.index', compact('orders', 'legacyPurchases'));
    }

    public function create()
    {
        $medicines = Medicine::orderBy('name')->get(['id', 'code', 'name', 'unit', 'purchase_price']);
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.create', compact('medicines', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $supplier = Supplier::findOrFail($data['supplier_id']);
        $subtotalCents = 0;
        foreach ($data['items'] as $index => $item) {
            $lineCents = (int) round((float) $item['unit_cost'] * 100) * (int) $item['quantity'];
            $subtotalCents += $lineCents;

            if ($subtotalCents > 99999999999999) {
                return back()->withInput()->withErrors([
                    "items.{$index}.unit_cost" => 'Total pembelian melebihi batas maksimum.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $supplier, $subtotalCents) {
            $orderNumber = 'PO-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
            $order = PurchaseOrder::create([
                'order_number' => $orderNumber,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'total' => $this->centsToDecimal($subtotalCents),
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $index => $item) {
                Purchase::create([
                    'purchase_order_id' => $order->id,
                    'purchase_number' => $orderNumber.'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'medicine_id' => $item['medicine_id'],
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'status' => 'pending',
                    'note' => $data['note'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchases.index')->with('status', 'Pesanan pembelian dicatat dan menunggu konfirmasi. Stok belum berubah.');
    }

    public function confirm(Purchase $purchase, StockService $stockService)
    {
        DB::transaction(function () use ($purchase, $stockService) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== 'pending') {
                abort(409, 'Pembelian ini sudah diproses.');
            }

            if ($purchase->purchase_order_id !== null) {
                abort(409, 'Konfirmasikan pesanan pembelian secara keseluruhan.');
            }

            $medicine = Medicine::whereKey($purchase->medicine_id)->lockForUpdate()->firstOrFail();
            $stockService->increaseLocked(
                $medicine,
                $purchase->quantity,
                'purchase',
                $purchase->note,
                $purchase
            );

            $medicine->purchase_price = $purchase->unit_cost;
            $medicine->supplier_id = $purchase->supplier_id;
            $medicine->supplier_name = $purchase->supplier_name;
            $medicine->save();

            $purchase->status = 'confirmed';
            $purchase->confirmed_at = now();
            $purchase->save();
        });

        return redirect()->route('purchases.index')->with('status', 'Pembelian dikonfirmasi dan stok berhasil ditambahkan.');
    }

    public function confirmOrder(PurchaseOrder $purchaseOrder, StockService $stockService)
    {
        DB::transaction(function () use ($purchaseOrder, $stockService) {
            $order = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'pending') {
                abort(409, 'Pesanan ini sudah diproses.');
            }

            $items = Purchase::where('purchase_order_id', $order->id)
                ->orderBy('medicine_id')
                ->get();
            $medicineIds = $items->pluck('medicine_id')->unique()->sort()->values();
            $medicines = Medicine::whereIn('id', $medicineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($items->isEmpty() || $medicines->count() !== $medicineIds->count()) {
                abort(409, 'Item pada pesanan tidak lagi tersedia.');
            }

            $confirmedAt = now();
            foreach ($items as $item) {
                $medicine = $medicines->get($item->medicine_id);
                $stockService->increaseLocked($medicine, $item->quantity, 'purchase', $item->note, $item);

                $medicine->purchase_price = $item->unit_cost;
                $medicine->supplier_id = $order->supplier_id;
                $medicine->supplier_name = $order->supplier_name;
                $medicine->save();

                $item->status = 'confirmed';
                $item->confirmed_at = $confirmedAt;
                $item->save();
            }

            $order->status = 'confirmed';
            $order->confirmed_at = $confirmedAt;
            $order->save();
        });

        return redirect()->route('purchases.index')->with('status', 'Pesanan dikonfirmasi dan seluruh stok berhasil ditambahkan.');
    }

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}