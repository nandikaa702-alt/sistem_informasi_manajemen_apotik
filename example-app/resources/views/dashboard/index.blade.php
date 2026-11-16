@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .dash-heading { align-items: center; margin-bottom: 20px; }
    .dash-heading h1 { font-size: 24px; }
    .dash-heading .subtitle { margin-top: 5px; }
    .dash-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .dash-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; }
    .dash-card { position: relative; min-width: 0; padding: 17px 17px 15px; overflow: hidden; border: 1px solid var(--line); border-radius: 7px; background: #fff; box-shadow: 0 3px 12px rgba(24,53,45,.035); transition: transform .16s ease, box-shadow .16s ease; }
    .dash-card::before { position: absolute; inset: 0 auto 0 0; width: 3px; background: var(--green); content: ''; }
    .dash-card.blue::before { background: var(--blue); }
    .dash-card.sky::before { background: #25a3c7; }
    .dash-card.amber::before { background: #d29a31; }
    .dash-card-link { display: block; color: inherit; text-decoration: none; }
    .dash-card-link:hover .dash-card { border-color: #b6c8e8; box-shadow: 0 7px 20px rgba(31,83,151,.09); transform: translateY(-2px); }
    .dash-card-top { display: flex; align-items: center; justify-content: space-between; gap: 9px; }
    .dash-card-label { color: #647a72; font-size: 11px; font-weight: 700; }
    .dash-icon { display: grid; width: 32px; height: 32px; place-items: center; border-radius: 7px; background: var(--green-soft); color: var(--green); }
    .dash-card.blue .dash-icon { background: var(--blue-soft); color: var(--blue); }
    .dash-card.sky .dash-icon { background: #e8f7fa; color: #16809b; }
    .dash-card.amber .dash-icon { background: #fbf3df; color: #9a6d14; }
    .dash-icon svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .dash-card-value { display: block; margin-top: 13px; color: var(--ink); font: 800 24px/1.1 'Manrope', sans-serif; overflow-wrap: anywhere; }
    .dash-card-foot { margin-top: 6px; color: var(--muted); font-size: 10px; }
    .dash-alerts { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; margin-top: 12px; }
    .dash-alert { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 15px; border: 1px solid var(--line); border-radius: 6px; background: #fff; text-decoration: none; }
    .dash-alert:hover { border-color: #b6c8e8; }
    .dash-alert span { color: var(--muted); font-size: 11px; }
    .dash-alert strong { display: block; margin-bottom: 3px; color: var(--ink); font-size: 12px; }
    .dash-alert b { color: var(--blue); font: 800 18px 'Manrope', sans-serif; }
    .dash-charts { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 13px; margin-top: 22px; }
    .dash-panel { min-width: 0; overflow: hidden; border: 1px solid var(--line); border-radius: 7px; background: #fff; }
    .dash-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 15px 16px 0; }
    .dash-panel-head h2 { font: 700 13px 'Manrope', sans-serif; }
    .dash-panel-head p { margin-top: 4px; color: var(--muted); font-size: 10px; }
    .chart-label { color: #7b8d85; font-size: 9px; }
    .dash-chart { position: relative; height: 185px; margin: 5px 13px 10px; }
    .dash-chart svg { width: 100%; height: 100%; overflow: visible; }
    .dash-chart-grid { stroke: #e8eff1; stroke-width: 1; }
    .dash-chart-line { fill: none; stroke: var(--blue); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .dash-chart-fill { fill: url(#sales-fill); opacity: .14; }
    .dash-chart-point { fill: #fff; stroke: var(--blue); stroke-width: 2; }
    .dash-chart-empty { position: absolute; inset: 0; display: grid; place-content: center; color: #83948d; font-size: 11px; text-align: center; }
    .dash-chart-empty svg { display: block; width: 25px; height: 25px; margin: 0 auto 7px; color: #5a91e8; }
    .dash-axis { display: flex; justify-content: space-between; padding: 0 8px; color: #81928b; font-size: 9px; }
    .activity-head { display: flex; align-items: center; justify-content: space-between; padding: 15px 16px; }
    .activity-head h2 { font: 700 13px 'Manrope', sans-serif; }
    .activity-head a { color: var(--blue); font-size: 11px; font-weight: 700; text-decoration: none; }
    .activity-empty { padding: 24px 12px; color: var(--muted); font-size: 12px; text-align: center; }
    @media (max-width: 1050px) { .dash-metrics { grid-template-columns: repeat(2,minmax(0,1fr)); } }
    @media (max-width: 760px) { .dash-charts { grid-template-columns: 1fr; } }
    @media (max-width: 560px) {
        .dash-heading { align-items: flex-start; }
        .dash-heading h1 { font-size: 21px; }
        .dash-actions { width: 100%; }
        .dash-alerts { grid-template-columns: 1fr; }
        .dash-card { padding: 14px 13px; }
        .dash-card-value { font-size: 21px; }
    }
</style>
@endpush

@section('content')
    <header class="page-heading dash-heading">
        <div><h1>Dashboard</h1><p class="subtitle">Ringkasan kondisi apotek hari ini.</p></div>
        <div class="dash-actions">
            <a class="button secondary" href="{{ route('stock.index') }}">Kelola stok</a>
            <a class="button" href="{{ route('sales.create') }}">Buka kasir</a>
        </div>
    </header>

    <section class="dash-metrics" aria-label="Statistik apotek">
        <a class="dash-card-link" href="{{ route('medicines.index') }}"><article class="dash-card">
            <div class="dash-card-top"><span class="dash-card-label">Jenis obat</span><span class="dash-icon"><svg viewBox="0 0 24 24"><path d="m9 3 12 12-6 6L3 9l6-6Z"/><path d="m7 11 4-4"/></svg></span></div>
            <strong class="dash-card-value">{{ number_format($metrics['medicine_types'], 0, ',', '.') }}</strong><div class="dash-card-foot">Obat terdaftar</div>
        </article></a>
        <a class="dash-card-link" href="{{ route('stock.index') }}"><article class="dash-card blue">
            <div class="dash-card-top"><span class="dash-card-label">Total stok obat</span><span class="dash-icon"><svg viewBox="0 0 24 24"><path d="m3 7 9-4 9 4-9 4-9-4Z"/><path d="m3 12 9 4 9-4M3 17l9 4 9-4"/></svg></span></div>
            <strong class="dash-card-value">{{ number_format($metrics['stock_units'], 0, ',', '.') }}</strong><div class="dash-card-foot">Unit tersedia</div>
        </article></a>
        <a class="dash-card-link" href="{{ route('sales.index') }}"><article class="dash-card sky">
            <div class="dash-card-top"><span class="dash-card-label">Transaksi hari ini</span><span class="dash-icon"><svg viewBox="0 0 24 24"><path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3Z"/><path d="M8 8h8M8 12h8"/></svg></span></div>
            <strong class="dash-card-value">{{ number_format($metrics['today_transactions'], 0, ',', '.') }}</strong><div class="dash-card-foot">Struk penjualan</div>
        </article></a>
        <a class="dash-card-link" href="{{ route('reports.index', ['type' => 'sales_daily']) }}"><article class="dash-card blue">
            <div class="dash-card-top"><span class="dash-card-label">Pendapatan hari ini</span><span class="dash-icon"><svg viewBox="0 0 24 24"><path d="M12 2v20m5-15H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span></div>
            <strong class="dash-card-value">Rp {{ number_format($metrics['today_revenue'], 0, ',', '.') }}</strong><div class="dash-card-foot">Total penjualan</div>
        </article></a>
    </section>

    <section class="dash-alerts" aria-label="Peringatan apotek">
        <a class="dash-alert" href="{{ route('alerts.low-stock') }}"><span><strong>Stok minimum</strong>Obat perlu ditambah</span><b>{{ number_format($metrics['low_stock'], 0, ',', '.') }}</b></a>
        <a class="dash-alert" href="{{ route('alerts.expiring') }}"><span><strong>Obat kedaluwarsa</strong>Perlu ditindaklanjuti</span><b>{{ number_format($metrics['expired_medicines'], 0, ',', '.') }}</b></a>
    </section>

    <section class="dash-charts" aria-label="Grafik penjualan">
        <article class="dash-panel">
            <div class="dash-panel-head"><div><h2>Penjualan harian</h2><p>7 hari terakhir</p></div><span class="chart-label">Pendapatan (Rp)</span></div>
            <div class="dash-chart">
                <svg viewBox="0 0 440 180" preserveAspectRatio="none" role="img" aria-label="Grafik penjualan harian">
                    <defs><linearGradient id="sales-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#2867dc"/><stop offset="1" stop-color="#2867dc" stop-opacity="0"/></linearGradient></defs>
                    <line class="dash-chart-grid" x1="42" y1="25" x2="430" y2="25"/><line class="dash-chart-grid" x1="42" y1="70" x2="430" y2="70"/><line class="dash-chart-grid" x1="42" y1="115" x2="430" y2="115"/><line class="dash-chart-grid" x1="42" y1="160" x2="430" y2="160"/>
                    @if ($dailyChart['hasData'])
                        <polygon class="dash-chart-fill" points="{{ $dailyChart['points'] }} 414,160 44,160"/>
                        <polyline class="dash-chart-line" points="{{ $dailyChart['points'] }}"/>
                        @foreach (explode(' ', $dailyChart['points']) as $point)<circle class="dash-chart-point" cx="{{ explode(',', $point)[0] }}" cy="{{ explode(',', $point)[1] }}" r="3"/>@endforeach
                    @endif
                </svg>
                @unless ($dailyChart['hasData'])<div class="dash-chart-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 6-7"/></svg>Belum ada data penjualan</div>@endunless
            </div>
            <div class="dash-axis">@foreach ($dailyChart['labels'] as $label)<span>{{ $label }}</span>@endforeach</div>
        </article>
        <article class="dash-panel">
            <div class="dash-panel-head"><div><h2>Penjualan bulanan</h2><p>Tahun {{ now()->format('Y') }}</p></div><span class="chart-label">Pendapatan (Rp)</span></div>
            <div class="dash-chart">
                <svg viewBox="0 0 440 180" preserveAspectRatio="none" role="img" aria-label="Grafik penjualan bulanan">
                    <defs><linearGradient id="monthly-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#17684f"/><stop offset="1" stop-color="#17684f" stop-opacity="0"/></linearGradient></defs>
                    <line class="dash-chart-grid" x1="42" y1="25" x2="430" y2="25"/><line class="dash-chart-grid" x1="42" y1="70" x2="430" y2="70"/><line class="dash-chart-grid" x1="42" y1="115" x2="430" y2="115"/><line class="dash-chart-grid" x1="42" y1="160" x2="430" y2="160"/>
                    @if ($monthlyChart['hasData'])
                        <polygon class="dash-chart-fill" style="fill:url(#monthly-fill)" points="{{ $monthlyChart['points'] }} 414,160 44,160"/>
                        <polyline class="dash-chart-line" style="stroke:var(--green)" points="{{ $monthlyChart['points'] }}"/>
                        @foreach (explode(' ', $monthlyChart['points']) as $point)<circle class="dash-chart-point" style="stroke:var(--green)" cx="{{ explode(',', $point)[0] }}" cy="{{ explode(',', $point)[1] }}" r="2.5"/>@endforeach
                    @endif
                </svg>
                @unless ($monthlyChart['hasData'])<div class="dash-chart-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 3v18h18"/><path d="m7 14 4-4 3 3 6-7"/></svg>Belum ada data penjualan</div>@endunless
            </div>
            <div class="dash-axis">@foreach ($monthlyChart['labels'] as $label)<span>{{ $label }}</span>@endforeach</div>
        </article>
    </section>

    <section class="dash-panel" style="margin-top:14px">
        <div class="activity-head"><h2>Aktivitas transaksi terbaru</h2><a href="{{ route('sales.index') }}">Semua transaksi</a></div>
        <div class="table-wrap">
            @if ($recentTransactions->isEmpty())
                <div class="activity-empty">Belum ada transaksi penjualan.</div>
            @else
                <table>
                    <thead><tr><th>No. struk</th><th>Waktu</th><th>Jumlah item</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recentTransactions as $transaction)
                            <tr>
                                <td><strong>{{ $transaction->receipt_number }}</strong></td>
                                <td>{{ $transaction->sold_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $transaction->items_count }} item</td>
                                <td>Rp {{ number_format((float) $transaction->subtotal, 0, ',', '.') }}</td>
                                <td><a class="button secondary small" href="{{ route('sales.receipt', $transaction) }}">Lihat struk</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
@endsection