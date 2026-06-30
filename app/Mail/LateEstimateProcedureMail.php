<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Estimate;
use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LateEstimateProcedureMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly Company $company,
        public readonly int $daysAllowed,
        public readonly int $daysPendingEstimate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Late estimate: '.$this->lead->name.' has been pending '.$this->daysPendingEstimate.' days',
        );
    }

    public function content(): Content
    {
        $this->lead->loadMissing('source', 'estimates');
        $estimate = $this->lead->estimates->sortByDesc('updated_at')->first();

        return new Content(
            view: 'mail.leads.late-estimate',
            with: [
                'lead' => $this->lead,
                'company' => $this->company,
                'daysAllowed' => $this->daysAllowed,
                'daysPendingEstimate' => $this->daysPendingEstimate,
                'leadUrl' => route('leads.index', ['search' => $this->lead->name]),
                'estimateUrl' => $estimate instanceof Estimate
                    ? route('estimates.builder', $estimate)
                    : route('estimates.index', ['search' => $this->lead->name]),
            ],
        );
    }
}
