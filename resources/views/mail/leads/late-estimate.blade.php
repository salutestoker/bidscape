<p>A lead has reached the Late Estimate Procedure threshold for {{ $company->name }}.</p>

<p>
    <strong>{{ $lead->name }}</strong><br>
    Status: {{ $lead->status->label() }}<br>
    Pending Estimate age: {{ $daysPendingEstimate }} days<br>
    Company threshold: {{ $daysAllowed }} days
</p>

<p>
    Phone: {{ $lead->phone ?: 'Not provided' }}<br>
    Email: {{ $lead->email ?: 'Not provided' }}<br>
    Address: {{ trim(collect([$lead->site_address, $lead->city, $lead->state, $lead->postal_code])->filter()->join(', ')) ?: 'Not provided' }}<br>
    Source: {{ $lead->source?->name ?: 'Not provided' }}
</p>

<p>
    <strong>Project Interest</strong><br>
    {{ $lead->project_interest ?: 'Not provided' }}
</p>

<p>
    <strong>Requested Specs</strong><br>
    {{ $lead->requested_project_specifications ?: 'Not provided' }}
</p>

<p>
    Review the lead:<br>
    <a href="{{ $leadUrl }}">{{ $leadUrl }}</a>
</p>

<p>
    Review or create the estimate:<br>
    <a href="{{ $estimateUrl }}">{{ $estimateUrl }}</a>
</p>
