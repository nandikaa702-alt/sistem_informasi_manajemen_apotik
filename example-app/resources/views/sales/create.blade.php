@extends('layouts.app')

@section('title', 'Kasir')

@push('styles')
<style>
    .cashier-grid { display: grid; grid-template-columns: minmax(260px, .75fr) minmax(0, 1.5fr); gap: 14px; align-items: start; }
    .cashier-panel { padding: 17px; }
    .cashier-panel h2 { margin-bottom: 14px; }
    .search-results { display: grid; gap: 7px; margin: 10px 0 0; padding: 0; list-style: none; }
    .search-result { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 10px; padding: 10px; border: 1px solid var(--line); border-radius: 5px; background: #fff; color: var(--ink); text-align: left; cursor: pointer; }
    .search-result:hover { border-color: #9bb9a2; background: #f7faf6; }
    .search-result small { display: block; margin-top: 3px; color: var(--muted); }
    .result-price { white-space: nowrap; font-size: 11px; font-weight: 700; }
    .search-message { padding: 14px 5px; color: var(--muted); font-size: 12px; }
    .cart-table { min-width: 610px; }
    .cart-table input[type=number] { width: 78px; min-height: 34px; padding: 5px 7px; }
    .remove-item { display: grid; width: 30px; height: 30px; place-items: center; border: 1px solid #f0d7d1; border-radius: 5px; background: #fff; color: var(--red); cursor: pointer; }
    .cart-empty { padding: 27px 12px; color: var(--muted); text-align: center; }
    .totals { display: grid; gap: 10px; max-width: 330px; margin: 16px 0 0 auto; }
    .total-row { display: flex; justify-content: space-between; gap: 12px; color: #647068; }
    .total-row.grand { padding-top: 10px; border-top: 1px solid var(--line); color: var(--ink); font-size: 16px; font-weight: 800; }
    .payment-field { max-width: 330px; margin: 17px 0 0 auto; }
    .change { margin-top: 8px; color: var(--green); font-size: 13px; font-weight: 700; text-align: right; }
    .cashier-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 17px; }
    @media (max-width: 800px) { .cashier-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <header class="page-heading">
        <div><h1>Kasir</h1><p class="subtitle">Cari obat, susun keranjang, lalu simpan pembayaran.</p></div>
        <a class="button secondary" href="{{ route('sales.index') }}">Riwayat transaksi</a>
    </header>
    @if ($errors->any())
        <div class="error-summary" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    <div class="cashier-grid" data-search-url="{{ route('sales.medicine-search') }}">
        <section class="panel cashier-panel" aria-labelledby="medicine-search-title">
            <h2 id="medicine-search-title">Cari obat</h2>
            <label for="medicine-search">Nama atau kode obat</label>
            <input id="medicine-search" type="search" placeholder="Ketik nama atau kode..." autocomplete="off" aria-controls="medicine-results">
            <ul id="medicine-results" class="search-results" role="listbox" aria-live="polite">
                <li class="search-message">Mulai mengetik untuk mencari obat.</li>
            </ul>
        </section>

        <form class="panel cashier-panel" action="{{ route('sales.store') }}" method="POST" id="cashier-form">
            @csrf
            <h2>Keranjang</h2>
            <div class="table-wrap">
                <table class="cart-table">
                    <thead><tr><th>Obat</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody id="cart-items"><tr><td class="cart-empty" colspan="5">Keranjang masih kosong.</td></tr></tbody>
                </table>
            </div>
            <div class="totals">
                <div class="total-row"><span>Subtotal</span><strong id="subtotal-display">Rp 0</strong></div>
                <div class="total-row grand"><span>Total pembayaran</span><strong id="total-display">Rp 0</strong></div>
            </div>
            <div class="payment-field">
                <label for="paid-amount">Uang dibayarkan (Rp)</label>
                <input id="paid-amount" name="paid_amount" type="number" min="0" step="0.01" value="{{ old('paid_amount') }}" required>
                @error('paid_amount')<div class="field-error">{{ $message }}</div>@enderror
                <div class="change">Kembalian: <span id="change-display">Rp 0</span></div>
            </div>
            <div class="cashier-actions">
                <a class="button secondary" href="{{ route('sales.index') }}">Batal</a>
                <button class="button" id="checkout-button" type="submit" disabled>Simpan & cetak struk</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const layout = document.querySelector('.cashier-grid');
            const searchInput = document.getElementById('medicine-search');
            const results = document.getElementById('medicine-results');
            const cartBody = document.getElementById('cart-items');
            const paidInput = document.getElementById('paid-amount');
            const submitButton = document.getElementById('checkout-button');
            const cart = new Map();
            let searchTimer;
            let requestNumber = 0;
            const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 });
            const formatMoney = cents => currency.format(cents / 100);

            function updateTotals() {
                const subtotal = [...cart.values()].reduce((sum, item) => sum + item.priceCents * item.quantity, 0);
                const paid = Math.max(0, Math.round((Number(paidInput.value) || 0) * 100));
                document.getElementById('subtotal-display').textContent = formatMoney(subtotal);
                document.getElementById('total-display').textContent = formatMoney(subtotal);
                document.getElementById('change-display').textContent = formatMoney(Math.max(0, paid - subtotal));
                submitButton.disabled = cart.size === 0 || paid < subtotal;
            }

            function renderCart() {
                cartBody.replaceChildren();
                if (cart.size === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 5;
                    cell.className = 'cart-empty';
                    cell.textContent = 'Keranjang masih kosong.';
                    row.append(cell);
                    cartBody.append(row);
                    updateTotals();
                    return;
                }

                [...cart.values()].forEach((item, index) => {
                    const row = document.createElement('tr');
                    const medicineCell = document.createElement('td');
                    const medicineName = document.createElement('strong');
                    medicineName.textContent = item.name;
                    const medicineCode = document.createElement('small');
                    medicineCode.className = 'description';
                    medicineCode.textContent = item.code;
                    medicineCell.append(medicineName, medicineCode);

                    const priceCell = document.createElement('td');
                    priceCell.textContent = formatMoney(item.priceCents);
                    const quantityCell = document.createElement('td');
                    const quantityInput = document.createElement('input');
                    quantityInput.type = 'number';
                    quantityInput.name = `items[${index}][quantity]`;
                    quantityInput.min = '1';
                    quantityInput.max = String(item.stock);
                    quantityInput.step = '1';
                    quantityInput.value = String(item.quantity);
                    quantityInput.required = true;
                    quantityInput.setAttribute('aria-label', `Jumlah ${item.name}`);
                    quantityInput.addEventListener('input', () => {
                        item.quantity = Math.min(item.stock, Math.max(1, Number(quantityInput.value) || 1));
                        quantityInput.value = String(item.quantity);
                        renderCart();
                    });
                    quantityCell.append(quantityInput);

                    const subtotalCell = document.createElement('td');
                    subtotalCell.textContent = formatMoney(item.priceCents * item.quantity);
                    const actionCell = document.createElement('td');
                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'remove-item';
                    removeButton.textContent = '×';
                    removeButton.title = `Hapus ${item.name} dari keranjang`;
                    removeButton.addEventListener('click', () => {
                        cart.delete(item.id);
                        renderCart();
                    });
                    actionCell.append(removeButton);
                    const medicineId = document.createElement('input');
                    medicineId.type = 'hidden';
                    medicineId.name = `items[${index}][medicine_id]`;
                    medicineId.value = String(item.id);
                    actionCell.append(medicineId);
                    row.append(medicineCell, priceCell, quantityCell, subtotalCell, actionCell);
                    cartBody.append(row);
                });
                updateTotals();
            }

            function addMedicine(medicine) {
                const current = cart.get(medicine.id);
                if (current) {
                    current.quantity = Math.min(current.stock, current.quantity + 1);
                } else {
                    cart.set(medicine.id, {
                        id: medicine.id,
                        code: medicine.code,
                        name: medicine.name,
                        stock: Number(medicine.stock),
                        priceCents: Math.round(Number(medicine.sale_price) * 100),
                        quantity: 1,
                    });
                }
                renderCart();
                searchInput.focus();
            }

            function renderResults(medicines, message) {
                results.replaceChildren();
                if (medicines.length === 0) {
                    const item = document.createElement('li');
                    item.className = 'search-message';
                    item.textContent = message;
                    results.append(item);
                    return;
                }

                medicines.forEach(medicine => {
                    const item = document.createElement('li');
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'search-result';
                    button.setAttribute('role', 'option');
                    const detail = document.createElement('span');
                    detail.textContent = `${medicine.name} · ${medicine.code}`;
                    const stock = document.createElement('small');
                    stock.textContent = `Stok ${medicine.stock} ${medicine.unit}`;
                    detail.append(stock);
                    const price = document.createElement('span');
                    price.className = 'result-price';
                    price.textContent = formatMoney(Math.round(Number(medicine.sale_price) * 100));
                    button.append(detail, price);
                    button.addEventListener('click', () => addMedicine(medicine));
                    item.append(button);
                    results.append(item);
                });
            }

            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                const query = searchInput.value.trim();
                if (query.length === 0) {
                    requestNumber++;
                    renderResults([], 'Mulai mengetik untuk mencari obat.');
                    return;
                }
                renderResults([], 'Mencari obat...');
                const currentRequest = ++requestNumber;
                searchTimer = setTimeout(async () => {
                    try {
                        const url = new URL(layout.dataset.searchUrl, window.location.origin);
                        url.searchParams.set('q', query);
                        const response = await fetch(url, { headers: { Accept: 'application/json' } });
                        if (!response.ok) throw new Error('Pencarian gagal. Coba lagi.');
                        const medicines = await response.json();
                        if (currentRequest === requestNumber) renderResults(medicines, 'Obat tidak ditemukan atau stok kosong.');
                    } catch (error) {
                        if (currentRequest === requestNumber) renderResults([], error.message);
                    }
                }, 180);
            });

            paidInput.addEventListener('input', updateTotals);
            document.getElementById('cashier-form').addEventListener('submit', event => {
                const subtotal = [...cart.values()].reduce((sum, item) => sum + item.priceCents * item.quantity, 0);
                const paid = Math.round((Number(paidInput.value) || 0) * 100);
                if (cart.size === 0 || paid < subtotal) event.preventDefault();
            });
            updateTotals();
        })();
    </script>
@endsection