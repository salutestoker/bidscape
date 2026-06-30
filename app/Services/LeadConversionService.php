<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Models\Lead;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\DB;

class LeadConversionService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly LeadStatusWorkflow $leadStatuses,
    ) {}

    public function convertToEstimate(Lead $lead, ?string $projectName = null): Estimate
    {
        return DB::transaction(function () use ($lead, $projectName): Estimate {
            $paymentTerm = PaymentTerm::where('company_id', $lead->company_id)
                ->where('is_default', true)
                ->first();

            $estimate = Estimate::create([
                'company_id' => $lead->company_id,
                'customer_id' => $lead->customer_id,
                'lead_id' => $lead->id,
                'payment_term_id' => $paymentTerm?->id,
                'estimate_number' => $this->nextEstimateNumber($lead->company_id),
                'project_name' => $projectName ?: "{$lead->name} Landscape Project",
                'status' => EstimateStatus::Draft,
                'scope_summary' => $lead->requested_project_specifications ?: $lead->project_interest,
            ]);

            $this->leadStatuses->markPendingEstimate($lead);

            $this->activity->log($lead->company_id, ActivityEvent::LeadConverted, 'Lead converted to estimate.', $lead, $lead->assigned_user_id, [
                'estimate_id' => $estimate->id,
            ]);

            return $estimate;
        });
    }

    private function nextEstimateNumber(int $companyId): string
    {
        $next = Estimate::where('company_id', $companyId)->count() + 1;

        return 'EST-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
