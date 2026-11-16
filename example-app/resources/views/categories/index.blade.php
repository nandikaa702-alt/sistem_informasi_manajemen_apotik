@extends('layouts.app')

@section('title', 'Kategori Obat')

@section('content')
    <header class="page-heading">
        <div>
            <h1>Kategori Obat</h1>
            <p class="subtitle">Kelola pengelompokan obat yang digunakan pada data persediaan.</p>
        </div>
        <a class="button" href="{{ route('categories.create') }}"><span aria-hidden="true">+</span> Tambah kategori</a>
    </header>

    <div class="toolbar">
        <form class="search" action="{{ route('categories.index') }}" method="GET" role="search">
            <label for="search" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">Cari kategori</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Cari nama kategori...">
            <button class="button secondary" type="submit">Cari</button>
            @if ($search !== '')
                <a class="button secondary" href="{{ route('categories.index') }}" aria-label="Hapus pencarian">Reset</a>
            @endif
        </form>
        <div class="count">{{ number_format($categories->total(), 0, ',', '.') }} kategori</div>
    </div>

    <section class="panel" aria-label="Daftar kategori obat">
        @if ($categories->isEmpty())
            <div class="empty">
                <h2>{{ $search !== '' ? 'Kategori tidak ditemukan' : 'Belum ada kategori' }}</h2>
                <p class="subtitle">{{ $search !== '' ? 'Coba kata kunci lain.' : 'Tambahkan kategori agar dapat dipilih pada form obat.' }}</p>
                @if ($search === '')
                    <a class="button" style="margin-top:16px" href="{{ route('categories.create') }}">Tambah kategori pertama</a>
                @endif
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nama kategori</th><th>Deskripsi</th><th>Jumlah obat</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="medicine-name">{{ $category->name }}</td>
                                <td>{{ $category->description ?: '—' }}</td>
                                <td>{{ number_format($category->medicines_count, 0, ',', '.') }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="button secondary small" href="{{ route('categories.edit', $category) }}">Ubah</a>
                                        <form class="inline-form" action="{{ route('categories.destroy', $category) }}" method="POST" data-confirm="Hapus kategori {{ $category->name }}?" onsubmit="return confirm(this.dataset.confirm)">
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
            @if ($categories->hasPages())
                <div class="pagination">{{ $categories->links() }}</div>
            @endif
        @endif
    </section>
@endsection