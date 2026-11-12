<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin-bottom: 0; }
        h2 { font-size: 12px; margin: 4px 0 12px; font-weight: normal; color: #444; }
        .meta { color: #666; margin-bottom: 12px; }
        .header { border-bottom: 2px solid #0a2540; padding-bottom: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
        .totals { margin: 8px 0; font-weight: bold; }
        .footer { margin-top: 20px; color: #666; border-top: 1px solid #ccc; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $clinic['name'] }}</h1>
        <div class="meta">{{ $clinic['address'] }} {{ $clinic['phone'] }} {{ $clinic['email'] }}</div>
    </div>

    <h2>{{ $title }} — {{ $from }} to {{ $to }}</h2>
    <div class="meta">Generated {{ $generatedAt->format('d/m/Y H:i') }} by {{ $generatedBy }}</div>

    @if (!empty($totals))
        <div class="totals">
            @foreach ($totals as $total){{ $total['label'] }}: {{ $total['value'] }}&nbsp;&nbsp;@endforeach
        </div>
    @endif

    <table>
        <thead><tr>@foreach ($columns as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($columns) }}">No data.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($clinic['signature'])
        <div class="footer">{{ $clinic['signature'] }} — Signature: ______________________</div>
    @endif
    @if ($clinic['footer'])
        <div class="footer">{{ $clinic['footer'] }}</div>
    @endif
</body>
</html>
