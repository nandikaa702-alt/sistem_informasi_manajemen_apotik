@extends('layouts.app')

@section('title', 'Data Obat')

@section('content')
    <header class="page-heading">
        <div>
            <h1>Data Obat</h1>
            <p class="subtitle">Kelola informasi, harga, stok, dan masa berlaku obat.</p>
        </div>
        @if (auth()->user()->role !== \App\Models\User::ROLE_KASIR)
            <a class="button" href="{{ route('medicines.create') }}"><span aria-hidden="true">+</span> Tambah obat</a>
        @endif
    </header>

    <div class="toolbar">
        <form class="search" action="{{ route('medicines.index') }}" method="GET" role="search">
            <label for="search" class="sr-only" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Cari obat</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Cari nama atau kode obat...">
            <button class="button secondary" type="submit">Cari</button>
            @if ($search !== '')
                <a class="button secondary" href="{{ route('medicines.index') }}" aria-label="Hapus pencarian">Reset</a>
            @endif
        </form>
        <div class="count">{{ number_format($medicines->total(), 0, ',', '.') }} obat</div>
    </div>

    <section class="panel" aria-label="Daftar obat">
        @if ($medicines->isEmpty())
            <div class="empty">
                <h2>{{ $search !== '' ? 'Obat tidak ditemukan' : 'Belum ada data obat' }}</h2>
                <p class="subtitle">{{ $search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan data obat untuk mulai mengelola persediaan.' }}</p>
                @if ($search === '' && auth()->user()->role !== \App\Models\User::ROLE_KASIR)
                    <a class="button" style="margin-top:16px" href="{{ route('medicines.create') }}">Tambah obat pertama</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Kode / Nama obat</th>
                            <th>Kategori / Jenis</th>
                            <th>Satuan</th>
                            <th>Harga beli</th>
                            <th>Harga jual</th>
                            <th>Stok</th>
                            <th>Kedaluwarsa</th>
                            <th>Supplier</th>
                            @if (auth()->user()->role !== \App\Models\User::ROLE_KASIR)<th>Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($medicines as $medicine)
                            <tr>
                                <td><div class="medicine-name">{{ $medicine->name }}</div><div class="description">{{ $medicine->code }}{{ $medicine->description ? ' · '.$medicine->description : '' }}</div></td>
                                <td>{{ $medicine->category->name }}<div class="description">{{ $medicine->type }}</div></td>
                                <td>{{ $medicine->unit }}</td>
                                <td>Rp {{ number_format((float) $medicine->purchase_price, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) $medicine->sale_price, 0, ',', '.') }}</td>
                                <td>{{ number_format($medicine->stock, 0, ',', '.') }}</td>
                                <td>{{ $medicine->expires_at->format('d/m/Y') }}</td>
                                <td>{{ $medicine->supplier?->name ?? $medicine->supplier_name }}</td>
                                @if (auth()->user()->role !== \App\Models\User::ROLE_KASIR)
                                    <td>
                                        <div class="row-actions">
                                            <a class="button secondary small" href="{{ route('medicines.edit', $medicine) }}">Ubah</a>
                                            <form class="inline-form" action="{{ route('medicines.destroy', $medicine) }}" method="POST" data-confirm="Hapus data obat {{ $medicine->name }}?" onsubmit="return confirm(this.dataset.confirm)">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button danger small" type="submit">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($medicines->hasPages())
                <div class="pagination">{{ $medicines->links() }}</div>
            @endif
        @endif
    </section>
@endsection