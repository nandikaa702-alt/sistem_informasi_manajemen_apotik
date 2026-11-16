<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $medicines = Medicine::query()
            ->with(['category', 'supplier'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('medicines.index', compact('medicines', 'search'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('medicines.create', ['medicine' => new Medicine(), 'categories' => $categories, 'suppliers' => $suppliers]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $openingStock = (int) $data['stock'];
        $data['supplier_name'] = Supplier::findOrFail($data['supplier_id'])->name;
        $data['stock'] = 0;

        DB::transaction(function () use ($data, $openingStock) {
            $medicine = Medicine::create($data);

            if ($openingStock > 0) {
                $medicine->stock = $openingStock;
                $medicine->save();
                StockMovement::create([
                    'medicine_id' => $medicine->id,
                    'direction' => 'in',
                    'source' => 'opening',
                    'quantity' => $openingStock,
                    'stock_before' => 0,
                    'stock_after' => $openingStock,
                    'note' => 'Stok awal saat pendaftaran obat.',
                ]);
            }
        });

        return redirect()->route('medicines.index')->with('status', 'Data obat berhasil ditambahkan.');
    }

    public function edit(Medicine $medicine)
    {
        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('medicines.edit', compact('medicine', 'categories', 'suppliers'));
    }

    public function update(Request $request, Medicine $medicine)
    {
        $data = $this->validatedData($request, $medicine);
        $data['supplier_name'] = Supplier::findOrFail($data['supplier_id'])->name;
        $medicine->update($data);

        return redirect()->route('medicines.index')->with('status', 'Data obat berhasil diperbarui.');
    }

    public function destroy(Medicine $medicine)
    {
        if ($medicine->stockMovements()->exists() || $medicine->sales()->exists() || $medicine->purchases()->exists()) {
            return redirect()->route('medicines.index')->with('error', 'Obat tidak dapat dihapus karena sudah memiliki riwayat stok atau transaksi.');
        }

        $medicine->delete();

        return redirect()->route('medicines.index')->with('status', 'Data obat berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Medicine $medicine = null): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:50', Rule::unique('medicines', 'code')->ignore($medicine)],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'type' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'sale_price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'expires_at' => ['required', 'date'],
            'supplier_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];

        if ($medicine === null) {
            $rules['stock'] = ['required', 'integer', 'min:0', 'max:4294967295'];
        }

        return $request->validate($rules);
    }
}