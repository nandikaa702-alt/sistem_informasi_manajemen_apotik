@extends('layouts.app')

@section('title', 'Struk '.$transaction->receipt_number)

@push('styles')
<style>
    .receipt-paper { max-width: 560px; margin: 0 auto; padding: 26px; }
    .receipt-header { padding-bottom: 17px; border-bottom: 1px dashed #bfc7bf; text-align: center; }
    .receipt-brand { font: 800 20px 'Manrope', sans-serif; }
    .receipt-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; margin: 14px 0; color: #5f6b63; font-size: 11px; }
    .receipt-meta span:nth-child(even) { text-align: right; }
    .receipt-lines { width: 100%; border-collapse: collapse; }
    .receipt-lines th, .receipt-lines td { padding: 8px 4px; background: #fff; }
    .receipt-lines th { border-bottom: 1px solid var(--line); }
    .receipt-lines td { border-bottom: 1px dashed #dfe3dd; }
    .receipt-lines .numeric { text-align: right; }
    .receipt-total { display: grid; gap: 8px; margin-top: 14px; }
    .receipt-total div { display: flex; justify-content: space-between; gap: 12px; }
    .receipt-total .grand { padding-top: 9px; border-top: 1px solid var(--line); font-size: 16px; font-weight: 800; }
    .receipt-thanks { margin-top: 22px; color: var(--muted); font-size: 11px; text-align: center; }
    .receipt-actions { display: flex; justify-content: center; gap: 8px; margin: 15px auto; }
    @media print {
        .receipt-paper { max-width: 380px; padding: 0; box-shadow: none; }
        .no-print { display: none !important; }
        body { background: #fff; }
    }
</style>
@endpush

@section('content')
    <div class="receipt-actions no-print">
        <a class="button secondary" href="{{ route('sales.index') }}">Kembali</a>
        <button class="button" type="button" onclick="window.print()">Cetak struk</button>
        <a class="button" href="{{ route('sales.create') }}">Transaksi baru</a>
    </div>
    <article class="panel receipt-paper">
        <header class="receipt-header">
            <div class="receipt-brand">Apotek Sehat</div>
            <div class="subtitle">Bukti transaksi penjualan</div>
        </header>
        <div class="receipt-meta">
            <span>No. struk</span><span>{{ $transaction->receipt_number }}</span>
            <span>Tanggal</span><span>{{ $transaction->sold_at->format('d/m/Y H:i') }}</span>
        </div>
        <table class="receipt-lines">
            <thead><tr><th>Obat</th><th class="numeric">Qty</th><th class="numeric">Harga</th><th class="numeric">Jumlah</th></tr></thead>
            <tbody>
                @foreach ($transaction->items as $item)
                    <tr>
                        <td>{{ $item->medicine->name }}</td>
                        <td class="numeric">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td class="numeric">{{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                        <td class="numeric">{{ number_format((float) $item->total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="receipt-total">
            <div><span>Subtotal</span><strong>Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</strong></div>
            <div><span>Dibayar</span><strong>Rp {{ number_format((float) $transaction->paid_amount, 0, ',', '.') }}</strong></div>
            <div class="grand"><span>Kembalian</span><strong>Rp {{ number_format((float) $transaction->change_amount, 0, ',', '.') }}</strong></div>
        </div>
        <div class="receipt-thanks">Terima kasih atas kunjungan Anda.</div>
    </article>
@endsection