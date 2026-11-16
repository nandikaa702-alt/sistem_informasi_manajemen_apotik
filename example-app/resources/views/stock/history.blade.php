@extends('layouts.app')

@section('title', 'Riwayat Stok')

@section('content')
    <header class="page-heading">
        <div><h1>Riwayat Perubahan Stok</h1><p class="subtitle">Catatan penambahan, penjualan, pembelian, dan stok awal.</p></div>
        <a class="button secondary" href="{{ route('stock.index') }}">Kembali ke stok</a>
    </header>
    <section class="panel table-wrap">
        @if ($movements->isEmpty())
            <div class="empty">Belum ada perubahan stok.</div>
        @else
            <table>
                <thead><tr><th>Waktu</th><th>Obat</th><th>Sumber</th><th>Jumlah</th><th>Stok awal</th><th>Stok akhir</th><th>Catatan / Referensi</th></tr></thead>
                <tbody>
                    @foreach ($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td><div class="medicine-name">{{ $movement->medicine->name }}</div><div class="description">{{ $movement->medicine->code }}</div></td>
                            <td>{{ ['opening' => 'Stok awal', 'adjustment' => 'Penambahan manual', 'sale' => 'Penjualan', 'purchase' => 'Pembelian'][$movement->source] ?? $movement->source }}</td>
                            <td>{{ $movement->direction === 'in' ? '+' : '-' }}{{ number_format($movement->quantity, 0, ',', '.') }}</td>
                            <td>{{ number_format($movement->stock_before, 0, ',', '.') }}</td>
                            <td>{{ number_format($movement->stock_after, 0, ',', '.') }}</td>
                            <td>{{ $movement->note ?: ($movement->sale?->sale_number ?? $movement->purchase?->purchase_number ?? '—') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($movements->hasPages())<div class="pagination">{{ $movements->links() }}</div>@endif
        @endif
    </section>
@endsection