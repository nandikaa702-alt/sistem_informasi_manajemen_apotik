@extends('layouts.app')

@section('title', 'Laporan')

@push('styles')
<style>
    .report-filters { display: grid; grid-template-columns: minmax(210px, 1.4fr) repeat(2, minmax(145px, .7fr)) auto; gap: 10px; align-items: end; padding: 15px; margin-bottom: 14px; }
    .report-filters .field { min-width: 0; }
    .report-filters input, .report-filters select { margin: 0; }
    .report-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(175px, 1fr)); gap: 10px; margin-bottom: 14px; }
    .summary-card { padding: 13px 15px; border: 1px solid var(--line); border-radius: 6px; background: #fff; }
    .summary-card span { display: block; color: var(--muted); font-size: 11px; }
    .summary-card strong { display: block; margin-top: 6px; font: 800 17px 'Manrope', sans-serif; }
    .report-note { margin: 0 0 14px; padding: 10px 12px; border: 1px solid #e8dfbf; border-radius: 5px; background: #fbf7e9; color: #766535; font-size: 11px; }
    .report-actions { display: flex; gap: 8px; margin-bottom: 12px; }
    @media (max-width: 760px) { .report-filters { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 480px) { .report-filters { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <header class="page-heading">
        <div><h1>Laporan</h1><p class="subtitle">Ringkasan penjualan, pembelian, stok, kedaluwarsa, dan keuntungan.</p></div>
    </header>

    <form class="panel report-filters" action="{{ route('reports.index') }}" method="GET">
        <div class="field">
            <label for="type">Jenis laporan</label>
            <select id="type" name="type">
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from">Dari tanggal</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] }}" required>
        </div>
        <div class="field">
            <label for="to">Sampai tanggal</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] }}" required>
        </div>
        <button class="button" type="submit">Tampilkan</button>
    </form>

    <header class="page-heading">
        <div><h2>{{ $report['title'] }}</h2><p class="subtitle">{{ $report['description'] }} · {{ $filters['from'] }} – {{ $filters['to'] }}</p></div>
    </header>

    <div class="report-summary">
        @foreach ($report['summary'] as $item)
            <article class="summary-card">
                <span>{{ $item['label'] }}</span>
                <strong>{{ $item['currency'] ? 'Rp '.number_format((float) $item['value'], 0, ',', '.') : number_format((float) $item['value'], 0, ',', '.') }}</strong>
            </article>
        @endforeach
    </div>

    @if ($report['note'])<div class="report-note">{{ $report['note'] }}</div>@endif

    <div class="report-actions">
        <a class="button secondary" href="{{ route('reports.export', ['format' => 'pdf'] + $filters) }}">Ekspor PDF</a>
        <a class="button secondary" href="{{ route('reports.export', ['format' => 'xlsx'] + $filters) }}">Ekspor Excel</a>
    </div>

    <section class="panel table-wrap">
        @if (empty($report['rows']))
            <div class="empty">Tidak ada data untuk filter yang dipilih.</div>
        @else
            <table>
                <thead><tr>@foreach ($report['headings'] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($report['rows'] as $row)
                        <tr>
                            @foreach ($row as $index => $value)
                                <td>{{ in_array($index, $report['currency_columns'], true) ? 'Rp '.number_format((float) $value, 0, ',', '.') : $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection