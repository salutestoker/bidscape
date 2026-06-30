<?php

namespace App\Services;

use App\Enums\EstimateStatus;
use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use Illuminate\Support\Carbon;

class LeadStatusWorkflow
{
    public function sync(Lead $lead): LeadStatus
    {
        $status = $this->determine($lead);

        if ($lead->getRawOriginal('status') === $status->value && ! $this->needsTrackingUpdate($lead, $status)) {
            return $status;
        }

        $lead->forceFill($this->updatesForStatus($lead, $status))->save();
        $lead->setRelation('estimates', $lead->relationLoaded('estimates') ? $lead->estimates : $lead->estimates()->get());

        return $status;
    }

    public function syncCompany(Company $company): void
    {
        Lead::where('company_id', $company->id)
            ->with('estimates:id,lead_id,status,sent_at,approved_at,signed_at')
            ->chunkById(100, function ($leads): void {
                foreach ($leads as $lead) {
                    $this->sync($lead);
                }
            });
    }

    public function preview(?string $siteVisitScheduledAt): LeadStatus
    {
        if (blank($siteVisitScheduledAt)) {
            return LeadStatus::PendingContact;
        }

        return Carbon::parse($siteVisitScheduledAt)->isFuture()
            ? LeadStatus::SiteVisit
            : LeadStatus::PendingEstimate;
    }

    public function markPendingEstimate(Lead $lead): LeadStatus
    {
        return $this->force($lead, LeadStatus::PendingEstimate);
    }

    public function markEstimateSent(Lead $lead): LeadStatus
    {
        return $this->force($lead, LeadStatus::EstimateSent);
    }

    public function markApproved(Lead $lead): LeadStatus
    {
        return $this->force($lead, LeadStatus::Approved);
    }

    public function markClosed(Lead $lead): LeadStatus
    {
        return $this->force($lead, LeadStatus::Closed);
    }

    public function determine(Lead $lead): LeadStatus
    {
        $lead->loadMissing('estimates:id,lead_id,status,sent_at,approved_at,signed_at');

        if ($lead->estimates->contains(fn ($estimate): bool => $estimate->status === EstimateStatus::Signed) || $lead->getRawOriginal('status') === LeadStatus::Approved->value) {
            return LeadStatus::Approved;
        }

        if ($lead->getRawOriginal('status') === LeadStatus::Closed->value) {
            return LeadStatus::Closed;
        }

        if ($lead->estimates->contains(fn ($estimate): bool => in_array($estimate->status, [
            EstimateStatus::Emailed,
            EstimateStatus::Approved,
            EstimateStatus::SignaturePending,
        ], true))) {
            return LeadStatus::EstimateSent;
        }

        if ($lead->estimates->contains(fn ($estimate): bool => in_array($estimate->status, [
            EstimateStatus::Draft,
            EstimateStatus::InReview,
            EstimateStatus::Declined,
            EstimateStatus::Expired,
        ], true))) {
            return LeadStatus::PendingEstimate;
        }

        if ($lead->site_visit_scheduled_at) {
            return $lead->site_visit_scheduled_at->isFuture()
                ? LeadStatus::SiteVisit
                : LeadStatus::PendingEstimate;
        }

        return LeadStatus::PendingContact;
    }

    private function force(Lead $lead, LeadStatus $status): LeadStatus
    {
        $lead->forceFill($this->updatesForStatus($lead, $status))->save();

        return $status;
    }

    /**
     * @return array<string, mixed>
     */
    private function updatesForStatus(Lead $lead, LeadStatus $status): array
    {
        $previousStatus = $lead->getRawOriginal('status');
        $updates = ['status' => $status];

        if ($status === LeadStatus::Approved) {
            $updates['won_at'] = $lead->won_at ?: now();
            $updates['lost_at'] = null;
        } elseif ($status === LeadStatus::Closed) {
            $updates['lost_at'] = $lead->lost_at ?: now();
            $updates['won_at'] = null;
        } else {
            $updates['won_at'] = null;
            $updates['lost_at'] = null;
        }

        if ($status === LeadStatus::PendingEstimate) {
            $updates['pending_estimate_started_at'] = $previousStatus === LeadStatus::PendingEstimate->value && $lead->pending_estimate_started_at
                ? $lead->pending_estimate_started_at
                : now();

            if ($previousStatus !== LeadStatus::PendingEstimate->value) {
                $updates['late_estimate_last_notified_at'] = null;
            }
        } elseif ($previousStatus === LeadStatus::PendingEstimate->value || $lead->pending_estimate_started_at || $lead->late_estimate_last_notified_at) {
            $updates['pending_estimate_started_at'] = null;
            $updates['late_estimate_last_notified_at'] = null;
        }

        return $updates;
    }

    private function needsTrackingUpdate(Lead $lead, LeadStatus $status): bool
    {
        if ($status === LeadStatus::PendingEstimate) {
            return $lead->pending_estimate_started_at === null;
        }

        return $lead->pending_estimate_started_at !== null || $lead->late_estimate_last_notified_at !== null;
    }
}
