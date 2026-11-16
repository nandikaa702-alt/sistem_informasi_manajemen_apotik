@extends('layouts.app')

@section('title', 'Pembelian')

@section('content')
    <header class="page-heading">
        <div><h1>Pembelian</h1><p class="subtitle">Stok bertambah setelah pembelian dikonfirmasi.</p></div>
        <a class="button" href="{{ route('purchases.create') }}">Catat pembelian</a>
    </header>
    <section class="panel table-wrap">
        @if ($orders->isEmpty())
            <div class="empty">Belum ada pembelian.</div>
        @else
            <table>
                <thead><tr><th>No. pesanan</th><th>Waktu</th><th>Pemasok</th><th>Item</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="medicine-name">{{ $order->order_number }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->supplier_name }}</td>
                            <td>{{ $order->items_count }} item</td>
                            <td>Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                            <td>{{ $order->status === 'confirmed' ? 'Dikonfirmasi' : 'Menunggu konfirmasi' }}</td>
                            <td>
                                @if ($order->status === 'pending')
                                    <form action="{{ route('purchases.orders.confirm', $order) }}" method="POST" onsubmit="return confirm('Konfirmasi pesanan ini dan tambahkan seluruh stok?')">
                                        @csrf
                                        <button class="button small" type="submit">Konfirmasi</button>
                                    </form>
                                @else
                                    {{ $order->confirmed_at->format('d/m/Y H:i') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" style="padding-top:0">
                                <details>
                                    <summary>Rincian item</summary>
                                    <div style="padding:8px 0;color:#5f6b63;font-size:12px">
                                        @foreach ($order->items as $item)
                                            <div>{{ $item->medicine->name }} · {{ number_format($item->quantity, 0, ',', '.') }} × Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }}</div>
                                        @endforeach
                                        @if ($order->note)<div>Catatan: {{ $order->note }}</div>@endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($orders->hasPages())<div class="pagination">{{ $orders->links() }}</div>@endif
        @endif
    </section>
    @if ($legacyPurchases->isNotEmpty())
        <section class="panel table-wrap" style="margin-top:14px">
            <table>
                <caption style="padding:12px;text-align:left;font-weight:700">Riwayat pembelian lama</caption>
                <thead><tr><th>No. pembelian</th><th>Obat</th><th>Pemasok</th><th>Jumlah</th><th>Harga beli</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @foreach ($legacyPurchases as $purchase)
                        <tr>
                            <td class="medicine-name">{{ $purchase->purchase_number }}</td>
                            <td>{{ $purchase->medicine->name }}</td>
                            <td>{{ $purchase->supplier_name }}</td>
                            <td>{{ number_format($purchase->quantity, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $purchase->unit_cost, 0, ',', '.') }}</td>
                            <td>{{ $purchase->status === 'confirmed' ? 'Dikonfirmasi' : 'Menunggu konfirmasi' }}</td>
                            <td>
                                @if ($purchase->status === 'pending')
                                    <form action="{{ route('purchases.confirm', $purchase) }}" method="POST" onsubmit="return confirm('Konfirmasi pembelian ini dan tambahkan stok?')">
                                        @csrf
                                        <button class="button small" type="submit">Konfirmasi</button>
                                    </form>
                                @else
                                    {{ $purchase->confirmed_at->format('d/m/Y H:i') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($legacyPurchases->hasPages())<div class="pagination">{{ $legacyPurchases->links() }}</div>@endif
        </section>
    @endif
@endsection