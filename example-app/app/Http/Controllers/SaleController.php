<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleTransaction;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index()
    {
        $transactions = SaleTransaction::withCount('items')
            ->with('items.medicine')
            ->latest('sold_at')
            ->paginate(20);
        $legacySales = Sale::whereNull('sale_transaction_id')
            ->with('medicine')
            ->latest('sold_at')
            ->paginate(20, ['*'], 'legacy_page');

        return view('sales.index', compact('transactions', 'legacySales'));
    }

    public function create()
    {
        return view('sales.create');
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);
        $search = trim($data['q']);

        $medicines = Medicine::query()
            ->where('stock', '>', 0)
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'code', 'name', 'stock', 'unit', 'sale_price']);

        return response()->json($medicines);
    }

    public function store(Request $request, StockService $stockService)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.medicine_id' => ['required', 'integer', 'distinct', 'exists:medicines,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'paid_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
        ]);

        $transaction = DB::transaction(function () use ($data, $stockService) {
            $itemData = collect($data['items'])->values();
            $medicineIds = $itemData->pluck('medicine_id')->map(fn ($id) => (int) $id)->sort()->values();
            $medicines = Medicine::whereIn('id', $medicineIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($medicines->count() !== $medicineIds->count()) {
                throw ValidationException::withMessages(['items' => 'Salah satu obat tidak lagi tersedia.']);
            }

            $today = now()->toDateString();
            $subtotalCents = 0;
            $lines = [];

            foreach ($itemData as $index => $item) {
                $medicine = $medicines->get((int) $item['medicine_id']);
                $quantity = (int) $item['quantity'];

                if ($medicine->expires_at->toDateString() < $today) {
                    throw ValidationException::withMessages([
                        "items.{$index}.medicine_id" => 'Obat '.$medicine->name.' sudah kedaluwarsa.',
                    ]);
                }

                if ($quantity > $medicine->stock) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'Stok '.$medicine->name.' tidak mencukupi. Tersedia: '.$medicine->stock.'.',
                    ]);
                }

                $unitPriceCents = (int) round((float) $medicine->sale_price * 100);
                $lineTotalCents = $unitPriceCents * $quantity;
                $subtotalCents += $lineTotalCents;

                if ($subtotalCents > 99999999999999) {
                    throw ValidationException::withMessages(['items' => 'Total transaksi melebihi batas maksimum.']);
                }

                $lines[] = [
                    'medicine' => $medicine,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'total_cents' => $lineTotalCents,
                ];
            }

            $paidAmountCents = (int) round((float) $data['paid_amount'] * 100);
            if ($paidAmountCents < $subtotalCents) {
                throw ValidationException::withMessages(['paid_amount' => 'Uang yang dibayarkan kurang dari total transaksi.']);
            }

            $receiptNumber = 'TRX-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
            $soldAt = now();
            $transaction = SaleTransaction::create([
                'receipt_number' => $receiptNumber,
                'subtotal' => $this->centsToDecimal($subtotalCents),
                'paid_amount' => $this->centsToDecimal($paidAmountCents),
                'change_amount' => $this->centsToDecimal($paidAmountCents - $subtotalCents),
                'sold_at' => $soldAt,
            ]);

            foreach ($lines as $index => $line) {
                $sale = Sale::create([
                    'sale_transaction_id' => $transaction->id,
                    'sale_number' => $receiptNumber.'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'medicine_id' => $line['medicine']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $this->centsToDecimal($line['unit_price_cents']),
                    'unit_cost' => $line['medicine']->purchase_price,
                    'total' => $this->centsToDecimal($line['total_cents']),
                    'sold_at' => $soldAt,
                ]);

                $stockService->decreaseLocked($line['medicine'], $line['quantity'], $sale);
            }

            return $transaction;
        });

        return redirect()->route('sales.receipt', $transaction)->with('status', 'Transaksi berhasil disimpan.');
    }

    public function receipt(SaleTransaction $saleTransaction)
    {
        $saleTransaction->load('items.medicine');

        return view('sales.receipt', ['transaction' => $saleTransaction]);
    }

    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}