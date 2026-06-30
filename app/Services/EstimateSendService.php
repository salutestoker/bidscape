<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\EstimateStatus;
use App\Mail\EstimateSentMail;
use App\Models\Attachment;
use App\Models\Estimate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstimateSendService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly EstimatePdfService $pdf,
        private readonly LeadStatusWorkflow $leadStatuses,
        private readonly CompanySettingsService $settings,
    ) {}

    public function send(Estimate $estimate, string $recipient, ?string $subject = null, ?string $message = null): string
    {
        $estimate->loadMissing('company', 'lead', 'customer');
        $this->settings->ensureDefaults($estimate->company);
        $template = $this->settings->templateFor($estimate->company, 'estimate');

        $pdfPath = $this->pdf->generate($estimate);
        $token = Str::random(56);
        $reviewUrl = route('estimate-review.show', $token);

        $estimate->forceFill([
            'status' => EstimateStatus::Emailed,
            'sent_at' => now(),
            'public_token_hash' => hash('sha256', $token),
            'public_token_expires_at' => now()->addDays(30),
        ])->save();

        if ($estimate->lead) {
            $this->leadStatuses->markEstimateSent($estimate->lead);
        }

        Attachment::create([
            'company_id' => $estimate->company_id,
            'attachable_type' => Estimate::class,
            'attachable_id' => $estimate->id,
            'original_filename' => basename($pdfPath),
            'display_name' => 'Estimate '.$estimate->estimate_number,
            'disk' => 'local',
            'path' => $pdfPath,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'type' => 'document',
            'size_bytes' => Storage::disk('local')->size($pdfPath),
            'metadata' => ['review_url' => $reviewUrl],
        ]);

        Mail::to($recipient)->send(new EstimateSentMail(
            estimate: $estimate,
            reviewUrl: $reviewUrl,
            pdfPath: $pdfPath,
            subjectLine: $subject ?: $template->email_subject,
            messageBody: $message ?: $template->email_body,
        ));

        $this->activity->log($estimate->company_id, ActivityEvent::EstimateEmailed, 'Estimate emailed to customer with public review link.', $estimate, null, [
            'recipient' => $recipient,
        ]);

        return $token;
    }
}
