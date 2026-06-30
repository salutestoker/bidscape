<?php

namespace App\Mail;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment as MailAttachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EstimateSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Estimate $estimate,
        public readonly string $reviewUrl,
        public readonly string $pdfPath,
        public readonly ?string $subjectLine = null,
        public readonly ?string $messageBody = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine ?: 'Your '.$this->estimate->company->name.' estimate is ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.estimates.sent',
            with: [
                'estimate' => $this->estimate,
                'reviewUrl' => $this->reviewUrl,
                'messageBody' => $this->messageBody,
            ],
        );
    }

    /**
     * @return array<int, MailAttachment>
     */
    public function attachments(): array
    {
        return [
            MailAttachment::fromStorageDisk('local', $this->pdfPath)
                ->as($this->estimate->estimate_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
