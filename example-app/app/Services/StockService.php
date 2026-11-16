<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function increaseLocked(
        Medicine $medicine,
        int $quantity,
        string $source,
        ?string $note = null,
        ?Purchase $purchase = null
    ): StockMovement {
        $before = $medicine->stock;
        $after = $before + $quantity;

        if ($after > 4294967295) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah stok melebihi kapasitas penyimpanan.',
            ]);
        }

        $medicine->stock = $after;
        $medicine->save();

        return StockMovement::create([
            'medicine_id' => $medicine->id,
            'direction' => 'in',
            'source' => $source,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'purchase_id' => $purchase?->id,
            'note' => $note,
        ]);
    }

    public function decreaseLocked(Medicine $medicine, int $quantity, Sale $sale): StockMovement
    {
        $before = $medicine->stock;

        if ($quantity > $before) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok obat tidak mencukupi. Stok tersedia: '.$before.'.',
            ]);
        }

        $after = $before - $quantity;
        $medicine->stock = $after;
        $medicine->save();

        return StockMovement::create([
            'medicine_id' => $medicine->id,
            'direction' => 'out',
            'source' => 'sale',
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'sale_id' => $sale->id,
        ]);
    }
}