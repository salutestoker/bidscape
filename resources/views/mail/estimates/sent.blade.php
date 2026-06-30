<p>{{ $messageBody ?: 'Your estimate is ready for review.' }}</p>

<p>
    <strong>{{ $estimate->project_name }}</strong><br>
    Estimate {{ $estimate->estimate_number }}<br>
    Total: ${{ number_format($estimate->selling_price_cents / 100, 2) }}
</p>

<p>
    Review, approve, sign, or decline the estimate here:<br>
    <a href="{{ $reviewUrl }}">{{ $reviewUrl }}</a>
</p>

<p>{{ $estimate->company->name }}</p>
