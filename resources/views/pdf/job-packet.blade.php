<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $packet->packet_number }}</title>
    <style>
        body { color: #0b1324; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; margin: 0; }
        .page { padding: 36px; }
        .brand { color: #087a3d; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { font-size: 28px; margin: 4px 0 8px; }
        h2 { border-bottom: 1px solid #dfe6e1; font-size: 16px; margin: 24px 0 10px; padding-bottom: 6px; }
        .muted { color: #5b667a; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; width: 50%; }
        table { border-collapse: collapse; width: 100%; }
        th { color: #5b667a; font-size: 10px; padding: 8px; text-align: left; text-transform: uppercase; }
        td { border-top: 1px solid #edf1ee; padding: 9px 8px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
<div class="page">
    @php($sections = $sections ?? ['header', 'overview', 'materials_scope', 'commercial_summary'])
    @foreach($sections as $section)
        @switch($section)
            @case('header')
                <div class="brand">{{ $packet->company->name }}</div>
                <h1>Job Packet</h1>
                <div class="muted">{{ $packet->packet_number }} · {{ $packet->job->project_name }}</div>
                @break

            @case('overview')
                <h2>Overview</h2>
                <div class="grid">
                    <div class="col">
                        <strong>Customer</strong><br>
                        {{ $packet->job->customer->name }}<br>
                        <span class="muted">{{ $packet->job->customer->phone }} · {{ $packet->job->customer->email }}</span>
                    </div>
                    <div class="col">
                        <strong>Site</strong><br>
                        {{ $packet->job->site_address }}<br>
                        <span class="muted">{{ $packet->job->site_notes }}</span>
                    </div>
                </div>
                @break

            @case('materials_scope')
                <h2>Materials And Scope</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="num">Qty</th>
                        <th>Unit</th>
                        <th class="num">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach(($packet->snapshot['items'] ?? []) as $item)
                        <tr>
                            <td><strong>{{ $item['name'] }}</strong><br><span class="muted">{{ $item['notes'] ?? $item['subtitle'] ?? '' }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $item['item_type'] ?? 'scope')) }}</td>
                            <td class="num">{{ $item['quantity'] }}</td>
                            <td>{{ $item['unit'] }}</td>
                            <td class="num">${{ number_format(($item['total_cents'] ?? 0) / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @break

            @case('commercial_summary')
                <h2>Commercial Summary</h2>
                <p><strong>Contract Value:</strong> ${{ number_format($packet->job->contract_value_cents / 100, 2) }}</p>
                <p><strong>Signed:</strong> {{ optional($packet->job->contract_signed_at)->format('M j, Y') }}</p>
                @break
        @endswitch
    @endforeach
</div>
</body>
</html>
