<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $suppliers = Supplier::query()
            ->withCount(['medicines', 'purchases'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function create()
    {
        return view('suppliers.create', ['supplier' => new Supplier()]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validatedData($request));

        return redirect()->route('suppliers.index')->with('status', 'Data pemasok berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validatedData($request, $supplier));

        return redirect()->route('suppliers.index')->with('status', 'Data pemasok berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->medicines()->exists() || $supplier->purchases()->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Pemasok tidak dapat dihapus karena masih digunakan oleh obat atau pembelian.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Data pemasok berhasil dihapus.');
    }

    private function validatedData(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplier)],
            'name' => ['required', 'string', 'max:150'],
            'company_name' => ['required', 'string', 'max:180'],
            'address' => ['required', 'string', 'max:2000'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', Rule::unique('suppliers', 'email')->ignore($supplier)],
        ]);
    }
}