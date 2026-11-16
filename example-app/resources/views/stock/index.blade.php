@extends('layouts.app')

@section('title', 'Manajemen Stok')

@push('styles')
<style>
    .stock-alerts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .stock-alert-link { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border: 1px solid var(--line); border-radius: 7px; background: #fff; text-decoration: none; }
    .stock-alert-link strong { font: 700 14px 'Manrope', sans-serif; }
    .stock-alert-link span { color: var(--muted); font-size: 11px; }
    .stock-layout { display: grid; grid-template-columns: minmax(240px, 290px) minmax(0, 1fr); gap: 14px; align-items: start; }
    .stock-form { padding: 16px; }
    .stock-form h2 { margin-bottom: 15px; }
    .stock-form .field { margin-bottom: 13px; }
    .stock-value.low { color: var(--red); font-weight: 700; }
    .stock-actions { display: flex; flex-wrap: wrap; gap: 5px; }
    @media (max-width: 860px) { .stock-layout { grid-template-columns: 1fr; } }
    @media (max-width: 520px) { .stock-alerts { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <header class="page-heading">
        <div><h1>Manajemen Stok</h1><p class="subtitle">Pantau persediaan dan catat penambahan stok.</p></div>
        <a class="button secondary" href="{{ route('stock.history') }}">Riwayat perubahan stok</a>
    </header>

    <div class="stock-alerts">
        <a class="stock-alert-link" href="{{ route('alerts.low-stock') }}"><span><strong>Stok minimum</strong><br>Perlu ditambah</span><strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong></a>
        <a class="stock-alert-link" href="{{ route('alerts.expiring') }}"><span><strong>Mendekati kedaluwarsa</strong><br>Dalam 90 hari</span><strong>{{ number_format($expiringCount, 0, ',', '.') }}</strong></a>
    </div>

    <div class="stock-layout">
        <section class="panel stock-form" aria-labelledby="restock-title">
            <h2 id="restock-title">Tambah stok</h2>
            @if ($errors->any())
                <div class="error-summary" role="alert">Periksa kembali isian yang ditandai.</div>
            @endif
            <form action="{{ route('stock.store') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="medicine_id">Obat <span aria-hidden="true">*</span></label>
                    <select id="medicine_id" name="medicine_id" required>
                        <option value="">Pilih obat</option>
                        @foreach ($restockMedicines as $medicine)
                            <option value="{{ $medicine->id }}" @selected((string) old('medicine_id') === (string) $medicine->id)>{{ $medicine->code }} · {{ $medicine->name }} (stok {{ $medicine->stock }})</option>
                        @endforeach
                    </select>
                    @error('medicine_id') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="quantity">Jumlah penambahan <span aria-hidden="true">*</span></label>
                    <input id="quantity" name="quantity" type="number" min="1" step="1" value="{{ old('quantity') }}" required>
                    @error('quantity') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="note">Catatan</label>
                    <textarea id="note" name="note" maxlength="500">{{ old('note') }}</textarea>
                    @error('note') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <button class="button" type="submit">Tambah stok</button>
            </form>
        </section>

        <section>
            <div class="toolbar">
                <form class="search" action="{{ route('stock.index') }}" method="GET" role="search">
                    <label for="search" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Cari obat</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Cari nama atau kode obat...">
                    <button class="button secondary" type="submit">Cari</button>
                </form>
            </div>
            <div class="panel table-wrap">
                @if ($medicines->isEmpty())
                    <div class="empty">Belum ada data obat.</div>
                @else
                    <table>
                        <thead><tr><th>Obat</th><th>Kategori</th><th>Stok</th><th>Minimum</th><th>Kedaluwarsa</th><th>Transaksi</th></tr></thead>
                        <tbody>
                            @foreach ($medicines as $medicine)
                                <tr>
                                    <td><div class="medicine-name">{{ $medicine->name }}</div><div class="description">{{ $medicine->code }}</div></td>
                                    <td>{{ $medicine->category->name }}</td>
                                    <td class="stock-value {{ $medicine->stock <= $medicine->minimum_stock ? 'low' : '' }}">{{ number_format($medicine->stock, 0, ',', '.') }} {{ $medicine->unit }}</td>
                                    <td>{{ number_format($medicine->minimum_stock, 0, ',', '.') }}</td>
                                    <td>{{ $medicine->expires_at->format('d/m/Y') }}</td>
                                    <td><div class="stock-actions"><a class="button secondary small" href="{{ route('sales.create', ['medicine_id' => $medicine->id]) }}">Jual</a><a class="button secondary small" href="{{ route('purchases.create', ['medicine_id' => $medicine->id]) }}">Beli</a></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($medicines->hasPages())<div class="pagination">{{ $medicines->links() }}</div>@endif
                @endif
            </div>
        </section>
    </div>
@endsection