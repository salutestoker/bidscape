<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $estimate->estimate_number }}</title>
    <style>
        body { color: #0b1324; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; margin: 0; }
        .page { padding: 36px; }
        .brand { color: #087a3d; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .header { border-bottom: 2px solid #087a3d; display: table; margin-bottom: 28px; padding-bottom: 18px; width: 100%; }
        .header > div { display: table-cell; vertical-align: top; width: 50%; }
        h1 { font-size: 30px; line-height: 1.1; margin: 6px 0 8px; }
        h2 { font-size: 16px; margin: 22px 0 10px; }
        .muted { color: #5b667a; }
        .right { text-align: right; }
        .card { border: 1px solid #dfe6e1; border-radius: 8px; padding: 16px; }
        .grid { display: table; margin-bottom: 18px; width: 100%; }
        .grid .col { display: table-cell; width: 50%; }
        table { border-collapse: collapse; width: 100%; }
        th { border-bottom: 1px solid #dfe6e1; color: #5b667a; font-size: 10px; padding: 9px 8px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #edf1ee; padding: 11px 8px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .total { color: #087a3d; font-size: 26px; font-weight: 700; }
        .summary td { border: 0; padding: 5px 0; }
    </style>
</head>
<body>
<div class="page">
    @php($sections = $sections ?? ['header', 'prepared_for', 'project_site', 'scope_summary', 'scope_items', 'price_summary', 'terms'])
    @foreach($sections as $section)
        @switch($section)
            @case('header')
                <div class="header">
                    <div>
                        <div class="brand">{{ $estimate->company->name }}</div>
                        <h1>Estimate</h1>
                        <div class="muted">{{ $estimate->project_name }}</div>
                    </div>
                    <div class="right muted">
                        <strong>{{ $estimate->estimate_number }}</strong><br>
                        Sent {{ optional($estimate->sent_at)->format('M j, Y') ?: now()->format('M j, Y') }}<br>
                        {{ $estimate->company->phone }}<br>
                        {{ $estimate->company->email }}
                    </div>
                </div>
                @break

            @case('prepared_for')
                <div class="card" style="margin-bottom:18px;">
                    <strong>Prepared For</strong><br>
                    {{ $estimate->customer?->name ?: $estimate->lead?->name }}<br>
                    <span class="muted">{{ $estimate->customer?->email ?: $estimate->lead?->email }}</span><br>
                    <span class="muted">{{ $estimate->customer?->phone ?: $estimate->lead?->phone }}</span>
                </div>
                @break

            @case('project_site')
                <div class="card" style="margin-bottom:18px;">
                    <strong>Project Site</strong><br>
                    {{ $estimate->customer?->site_address ?: $estimate->lead?->site_address }}<br>
                    <span class="muted">{{ $estimate->customer?->city ?: $estimate->lead?->city }} {{ $estimate->customer?->state ?: $estimate->lead?->state }} {{ $estimate->customer?->postal_code ?: $estimate->lead?->postal_code }}</span>
                </div>
                @break

            @case('scope_summary')
                @if($estimate->scope_summary)
                    <h2>Scope Summary</h2>
                    <p>{{ $estimate->scope_summary }}</p>
                @endif
                @break

            @case('scope_items')
                <h2>Scope Items</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th class="num">Qty</th>
                        <th>Unit</th>
                        <th class="num">Unit Price</th>
                        <th class="num">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($estimate->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->name }}</strong><br>
                                <span class="muted">{{ $item->subtitle }}</span>
                            </td>
                            <td class="num">{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="num">${{ number_format($item->unit_price_cents / 100, 2) }}</td>
                            <td class="num"><strong>${{ number_format($item->total_cents / 100, 2) }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @break

            @case('price_summary')
                <div style="margin-left:auto;margin-top:18px;width:260px;">
                    <table class="summary">
                        <tr><td>Direct Cost</td><td class="num">${{ number_format($estimate->direct_cost_cents / 100, 2) }}</td></tr>
                        <tr><td>Overhead</td><td class="num">${{ number_format($estimate->overhead_cents / 100, 2) }}</td></tr>
                        <tr><td>Profit</td><td class="num">${{ number_format($estimate->profit_cents / 100, 2) }}</td></tr>
                    </table>
                    <div class="muted">Selling Price</div>
                    <div class="total">${{ number_format($estimate->selling_price_cents / 100, 2) }}</div>
                </div>
                @break

            @case('terms')
                @if($estimate->terms)
                    <h2>Terms</h2>
                    <p>{{ $estimate->terms }}</p>
                @endif
                @break
        @endswitch
    @endforeach
</div>
</body>
</html>
