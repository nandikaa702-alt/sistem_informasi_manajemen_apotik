@extends('layouts.app')

@section('title', 'Catat Pembelian')

@push('styles')
<style>
    .purchase-panel { padding: 18px; }
    .purchase-top { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
    .purchase-add { display: flex; align-items: flex-end; gap: 9px; margin-bottom: 12px; }
    .purchase-add .field { flex: 1; min-width: 0; }
    .purchase-table { min-width: 640px; }
    .purchase-table input[type=number] { width: 120px; min-height: 34px; padding: 5px 7px; }
    .purchase-total { display: flex; justify-content: flex-end; gap: 32px; margin-top: 16px; font-size: 15px; }
    .purchase-total strong { color: var(--ink); }
    .purchase-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; padding-top: 15px; border-top: 1px solid var(--line); }
    .remove-purchase-item { display: grid; width: 30px; height: 30px; place-items: center; border: 1px solid #f0d7d1; border-radius: 5px; background: #fff; color: var(--red); cursor: pointer; }
    @media (max-width: 600px) { .purchase-top { grid-template-columns: 1fr; } .purchase-add { align-items: stretch; flex-direction: column; } .purchase-total { justify-content: space-between; gap: 12px; } }
</style>
@endpush

@section('content')
    <header class="page-heading">
        <div><h1>Catat Pembelian</h1><p class="subtitle">Pilih pemasok dan obat. Stok bertambah setelah pesanan dikonfirmasi.</p></div>
        <a class="button secondary" href="{{ route('purchases.index') }}">Riwayat pembelian</a>
    </header>
    @if ($errors->any())
        <div class="error-summary" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif
    <section class="panel purchase-panel">
        @if ($medicines->isEmpty() || $suppliers->isEmpty())
            <div class="empty">
                {{ $medicines->isEmpty() ? 'Tambahkan obat terlebih dahulu.' : 'Tambahkan pemasok terlebih dahulu.' }}
                @if ($suppliers->isEmpty()) <a href="{{ route('suppliers.create') }}">Tambah pemasok</a> @endif
            </div>
        @else
            <form action="{{ route('purchases.store') }}" method="POST" id="purchase-form">
                @csrf
                <div class="purchase-top">
                    <div class="field">
                        <label for="supplier_id">Pemasok <span aria-hidden="true">*</span></label>
                        <select id="supplier_id" name="supplier_id" required>
                            <option value="">Pilih pemasok</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->code }} · {{ $supplier->name }} — {{ $supplier->company_name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="field">
                        <label for="note">Catatan pembelian</label>
                        <input id="note" name="note" value="{{ old('note') }}" maxlength="500" placeholder="Nomor faktur atau catatan">
                        @error('note')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="purchase-add">
                    <div class="field">
                        <label for="medicine-choice">Obat yang dibeli</label>
                        <select id="medicine-choice">
                            <option value="">Pilih obat</option>
                            @foreach ($medicines as $medicine)
                                <option value="{{ $medicine->id }}" data-code="{{ $medicine->code }}" data-name="{{ $medicine->name }}" data-unit="{{ $medicine->unit }}" data-cost="{{ $medicine->purchase_price }}">{{ $medicine->code }} · {{ $medicine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="button secondary" type="button" id="add-purchase-item">Tambah obat</button>
                </div>

                <div class="table-wrap">
                    <table class="purchase-table">
                        <thead><tr><th>Obat</th><th>Jumlah</th><th>Harga beli / unit</th><th>Subtotal</th><th></th></tr></thead>
                        <tbody id="purchase-items"><tr><td class="cart-empty" colspan="5">Belum ada obat pada pembelian.</td></tr></tbody>
                    </table>
                </div>
                <div class="purchase-total"><span>Total pembelian</span><strong id="purchase-total">Rp 0</strong></div>
                @error('items')<div class="field-error" style="text-align:right">{{ $message }}</div>@enderror
                <div class="purchase-actions">
                    <a class="button secondary" href="{{ route('purchases.index') }}">Batal</a>
                    <button class="button" id="save-purchase" type="submit" disabled>Simpan pesanan</button>
                </div>
            </form>
        @endif
    </section>

    @if (!$medicines->isEmpty() && !$suppliers->isEmpty())
        <script>
            (() => {
                const selector = document.getElementById('medicine-choice');
                const itemsBody = document.getElementById('purchase-items');
                const totalDisplay = document.getElementById('purchase-total');
                const submitButton = document.getElementById('save-purchase');
                const supplierInput = document.getElementById('supplier_id');
                const items = new Map();
                const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 });
                const money = cents => currency.format(cents / 100);

                function render() {
                    itemsBody.replaceChildren();
                    let totalCents = 0;
                    if (items.size === 0) {
                        const row = document.createElement('tr');
                        const cell = document.createElement('td');
                        cell.colSpan = 5;
                        cell.className = 'cart-empty';
                        cell.textContent = 'Belum ada obat pada pembelian.';
                        row.append(cell);
                        itemsBody.append(row);
                    }

                    [...items.values()].forEach((item, index) => {
                        const row = document.createElement('tr');
                        const nameCell = document.createElement('td');
                        const name = document.createElement('strong');
                        name.textContent = `${item.name} · ${item.code}`;
                        nameCell.append(name);

                        const quantityCell = document.createElement('td');
                        const quantity = document.createElement('input');
                        quantity.type = 'number';
                        quantity.name = `items[${index}][quantity]`;
                        quantity.min = '1';
                        quantity.max = '1000000';
                        quantity.step = '1';
                        quantity.value = String(item.quantity);
                        quantity.required = true;
                        quantity.setAttribute('aria-label', `Jumlah ${item.name}`);
                        quantity.addEventListener('input', () => {
                            item.quantity = Math.max(1, Number(quantity.value) || 1);
                            quantity.value = String(item.quantity);
                            render();
                        });
                        quantityCell.append(quantity);

                        const costCell = document.createElement('td');
                        const cost = document.createElement('input');
                        cost.type = 'number';
                        cost.name = `items[${index}][unit_cost]`;
                        cost.min = '0';
                        cost.max = '9999999999.99';
                        cost.step = '0.01';
                        cost.value = (item.unitCostCents / 100).toFixed(2);
                        cost.required = true;
                        cost.setAttribute('aria-label', `Harga beli per unit ${item.name}`);
                        cost.addEventListener('input', () => {
                            item.unitCostCents = Math.max(0, Math.round((Number(cost.value) || 0) * 100));
                            render();
                        });
                        costCell.append(cost);

                        const subtotalCell = document.createElement('td');
                        const itemSubtotal = item.quantity * item.unitCostCents;
                        subtotalCell.textContent = money(itemSubtotal);
                        totalCents += itemSubtotal;

                        const actionCell = document.createElement('td');
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'remove-purchase-item';
                        remove.textContent = '×';
                        remove.title = `Hapus ${item.name}`;
                        remove.addEventListener('click', () => {
                            items.delete(item.id);
                            render();
                        });
                        actionCell.append(remove);

                        const medicineId = document.createElement('input');
                        medicineId.type = 'hidden';
                        medicineId.name = `items[${index}][medicine_id]`;
                        medicineId.value = String(item.id);
                        actionCell.append(medicineId);
                        row.append(nameCell, quantityCell, costCell, subtotalCell, actionCell);
                        itemsBody.append(row);
                    });

                    totalDisplay.textContent = money(totalCents);
                    submitButton.disabled = items.size === 0 || !supplierInput.value;
                }

                document.getElementById('add-purchase-item').addEventListener('click', () => {
                    const option = selector.options[selector.selectedIndex];
                    if (!option.value) return;
                    const id = Number(option.value);
                    const existing = items.get(id);
                    if (existing) {
                        existing.quantity += 1;
                    } else {
                        items.set(id, {
                            id,
                            code: option.dataset.code,
                            name: option.dataset.name,
                            quantity: 1,
                            unitCostCents: Math.round(Number(option.dataset.cost) * 100),
                        });
                    }
                    selector.value = '';
                    render();
                });
                supplierInput.addEventListener('change', render);
                render();
            })();
        </script>
    @endif
@endsection