<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { color:#1d2924; font:10px DejaVu Sans,sans-serif; }
        h1 { margin:0 0 5px; font-size:18px; }
        p { margin:0 0 14px; color:#657168; }
        .summary { margin:0 0 14px; }
        .summary td { padding:6px 12px 6px 0; border:0; }
        .note { margin:10px 0; padding:7px; background:#fbf7e9; color:#766535; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th { padding:7px; border:1px solid #dfe4de; background:#f2f5f0; text-align:left; }
        table.data td { padding:6px; border:1px solid #e7eae4; }
        .empty { padding:20px; color:#78817b; text-align:center; }
    </style>
</head>
<body>
    <h1>{{ $report['title'] }}</h1>
    <p>{{ $report['description'] }} | Periode {{ $filters['from'] }} sampai {{ $filters['to'] }}</p>
    <table class="summary"><tr>
        @foreach ($report['summary'] as $item)
            <td><strong>{{ $item['label'] }}:</strong> {{ $item['currency'] ? 'Rp '.number_format((float) $item['value'], 0, ',', '.') : number_format((float) $item['value'], 0, ',', '.') }}</td>
        @endforeach
    </tr></table>
    @if ($report['note'])<div class="note">{{ $report['note'] }}</div>@endif
    @if (empty($report['rows']))
        <div class="empty">Tidak ada data untuk filter yang dipilih.</div>
    @else
        <table class="data">
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
</body>
</html>