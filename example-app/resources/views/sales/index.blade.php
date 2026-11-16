@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')
    <header class="page-heading">
        <div><h1>Transaksi Penjualan</h1><p class="subtitle">Riwayat pembayaran dan tautan cetak struk.</p></div>
        <a class="button" href="{{ route('sales.create') }}">Buka kasir</a>
    </header>
    <section class="panel table-wrap">
        @if ($transactions->isEmpty())
            <div class="empty">Belum ada transaksi penjualan.</div>
        @else
            <table>
                <thead><tr><th>No. struk</th><th>Waktu</th><th>Item</th><th>Total</th><th>Dibayar</th><th>Kembalian</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td class="medicine-name">{{ $transaction->receipt_number }}</td>
                            <td>{{ $transaction->sold_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $transaction->items_count }} item</td>
                            <td>Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $transaction->paid_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $transaction->change_amount, 0, ',', '.') }}</td>
                            <td><a class="button secondary small" href="{{ route('sales.receipt', $transaction) }}">Lihat / cetak struk</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($transactions->hasPages())<div class="pagination">{{ $transactions->links() }}</div>@endif
        @endif
    </section>
    @if ($legacySales->isNotEmpty())
        <section class="panel table-wrap" style="margin-top:14px">
            <table>
                <caption style="padding:12px;text-align:left;font-weight:700">Riwayat transaksi lama</caption>
                <thead><tr><th>No. transaksi</th><th>Waktu</th><th>Obat</th><th>Jumlah</th><th>Harga satuan</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($legacySales as $sale)
                        <tr>
                            <td class="medicine-name">{{ $sale->sale_number }}</td>
                            <td>{{ $sale->sold_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->medicine->name }}</td>
                            <td>{{ number_format($sale->quantity, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $sale->unit_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $sale->total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($legacySales->hasPages())<div class="pagination">{{ $legacySales->links() }}</div>@endif
        </section>
    @endif
@endsection