@extends('layouts.app')

@section('title', $title)

@section('content')
    <header class="page-heading">
        <div><h1>{{ $title }}</h1><p class="subtitle">{{ $description }}</p></div>
        <a class="button secondary" href="{{ route('stock.index') }}">Kembali ke stok</a>
    </header>
    <section class="panel table-wrap">
        @if ($medicines->isEmpty())
            <div class="empty">Tidak ada obat yang perlu ditindaklanjuti.</div>
        @else
            <table>
                <thead><tr><th>Obat</th><th>Kategori</th><th>Stok</th><th>Minimum</th><th>Kedaluwarsa</th><th>Sisa hari</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($medicines as $medicine)
                        <tr>
                            <td><div class="medicine-name">{{ $medicine->name }}</div><div class="description">{{ $medicine->code }}</div></td>
                            <td>{{ $medicine->category->name }}</td>
                            <td>{{ number_format($medicine->stock, 0, ',', '.') }} {{ $medicine->unit }}</td>
                            <td>{{ number_format($medicine->minimum_stock, 0, ',', '.') }}</td>
                            <td>{{ $medicine->expires_at->format('d/m/Y') }}</td>
                            <td>{{ now()->startOfDay()->diffInDays($medicine->expires_at->startOfDay(), false) }}</td>
                            <td><a class="button secondary small" href="{{ route('stock.index', ['search' => $medicine->code]) }}">Kelola stok</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($medicines->hasPages())<div class="pagination">{{ $medicines->links() }}</div>@endif
        @endif
    </section>
@endsection