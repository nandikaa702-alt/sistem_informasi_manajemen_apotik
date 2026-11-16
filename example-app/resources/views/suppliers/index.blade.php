@extends('layouts.app')

@section('title', 'Pemasok')

@section('content')
    <header class="page-heading">
        <div>
            <h1>Data Pemasok</h1>
            <p class="subtitle">Kelola informasi kontak dan perusahaan pemasok obat.</p>
        </div>
        <a class="button" href="{{ route('suppliers.create') }}"><span aria-hidden="true">+</span> Tambah pemasok</a>
    </header>

    <div class="toolbar">
        <form class="search" action="{{ route('suppliers.index') }}" method="GET" role="search">
            <label for="search" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Cari pemasok</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Cari kode, nama, atau perusahaan...">
            <button class="button secondary" type="submit">Cari</button>
            @if ($search !== '')
                <a class="button secondary" href="{{ route('suppliers.index') }}" aria-label="Hapus pencarian">Reset</a>
            @endif
        </form>
        <div class="count">{{ number_format($suppliers->total(), 0, ',', '.') }} pemasok</div>
    </div>

    <section class="panel" aria-label="Daftar pemasok">
        @if ($suppliers->isEmpty())
            <div class="empty">
                <h2>{{ $search !== '' ? 'Pemasok tidak ditemukan' : 'Belum ada data pemasok' }}</h2>
                <p class="subtitle">{{ $search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan pemasok agar dapat dipilih pada data obat dan pembelian.' }}</p>
                @if ($search === '')
                    <a class="button" style="margin-top:16px" href="{{ route('suppliers.create') }}">Tambah pemasok pertama</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kode</th><th>Nama pemasok</th><th>Perusahaan</th><th>Alamat</th><th>Telepon</th><th>Email</th><th>Obat</th><th>Pembelian</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="medicine-name">{{ $supplier->code }}</td>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->company_name }}</td>
                                <td style="white-space:normal;min-width:170px">{{ $supplier->address }}</td>
                                <td>{{ $supplier->phone }}</td>
                                <td>{{ $supplier->email }}</td>
                                <td>{{ number_format($supplier->medicines_count, 0, ',', '.') }}</td>
                                <td>{{ number_format($supplier->purchases_count, 0, ',', '.') }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="button secondary small" href="{{ route('suppliers.edit', $supplier) }}">Ubah</a>
                                        <form class="inline-form" action="{{ route('suppliers.destroy', $supplier) }}" method="POST" data-confirm="Hapus pemasok {{ $supplier->name }}?" onsubmit="return confirm(this.dataset.confirm)">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button danger small" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($suppliers->hasPages())<div class="pagination">{{ $suppliers->links() }}</div>@endif
        @endif
    </section>
@endsection