<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ContractStatus;
use App\Enums\EstimateDeclineReasonType;
use App\Enums\EstimateStatus;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Estimate;
use Illuminate\Support\Facades\DB;

class EstimateAcceptanceService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly ContractSigningService $signer,
        private readonly LeadStatusWorkflow $leadStatuses,
    ) {}

    public function approve(Estimate $estimate, string $signatureName, ?string $signatureEmail = null, ?string $ipAddress = null): Contract
    {
        return DB::transaction(function () use ($estimate, $signatureName, $signatureEmail, $ipAddress): Contract {
            $estimate->loadMissing('lead', 'customer', 'paymentTerm');
            $customer = $estimate->customer ?: $this->customerFromEstimate($estimate, $signatureEmail);

            $estimate->forceFill([
                'customer_id' => $customer->id,
                'status' => EstimateStatus::Approved,
                'approved_at' => now(),
                'signed_at' => now(),
                'signature_name' => $signatureName,
                'signature_email' => $signatureEmail ?: $customer->email,
                'signature_ip' => $ipAddress,
            ])->save();

            if ($estimate->lead) {
                $estimate->lead->forceFill([
                    'customer_id' => $customer->id,
                ])->save();
                $this->leadStatuses->markApproved($estimate->lead);
            }

            $contract = Contract::firstOrCreate(
                ['estimate_id' => $estimate->id],
                [
                    'company_id' => $estimate->company_id,
                    'customer_id' => $customer->id,
                    'payment_term_id' => $estimate->payment_term_id,
                    'contract_number' => $this->nextContractNumber($estimate->company_id),
                    'status' => ContractStatus::Sent,
                    'total_cents' => $estimate->selling_price_cents,
                    'sent_at' => $estimate->sent_at ?: now(),
                ],
            );

            $contract->forceFill([
                'customer_id' => $customer->id,
                'payment_term_id' => $estimate->payment_term_id,
                'total_cents' => $estimate->selling_price_cents,
                'signature_name' => $signatureName,
                'signature_email' => $signatureEmail ?: $customer->email,
            ])->save();

            $this->signer->sign($contract, $signatureName);
            $this->activity->log($estimate->company_id, ActivityEvent::EstimateApproved, 'Estimate approved and signed through public review link.', $estimate);

            return $contract->refresh();
        });
    }

    public function decline(Estimate $estimate, EstimateDeclineReasonType $reasonType, ?string $reason = null): Estimate
    {
        $estimate->loadMissing('lead');

        $estimate->forceFill([
            'status' => EstimateStatus::Declined,
            'declined_at' => now(),
            'decline_reason_type' => $reasonType,
            'declined_reason' => $reason,
        ])->save();

        if ($estimate->lead) {
            if ($reasonType === EstimateDeclineReasonType::ReviseBid) {
                $this->leadStatuses->markPendingEstimate($estimate->lead);
            } else {
                $this->leadStatuses->markClosed($estimate->lead);
            }
        }

        $this->activity->log($estimate->company_id, ActivityEvent::EstimateDeclined, 'Estimate declined through public review link.', $estimate, null, [
            'decline_reason_type' => $reasonType->value,
            'reason' => $reason,
        ]);

        return $estimate->refresh();
    }

    private function customerFromEstimate(Estimate $estimate, ?string $signatureEmail): Customer
    {
        $lead = $estimate->lead;
        $email = $signatureEmail ?: $lead?->email;

        return Customer::firstOrCreate(
            [
                'company_id' => $estimate->company_id,
                'email' => $email,
            ],
            [
                'lead_source_id' => $lead?->lead_source_id,
                'name' => $lead?->name ?: $estimate->project_name,
                'phone' => $lead?->phone,
                'site_address' => $lead?->site_address,
                'city' => $lead?->city,
                'state' => $lead?->state,
                'postal_code' => $lead?->postal_code,
                'notes' => $lead?->site_notes,
                'last_activity_at' => now(),
            ],
        );
    }

    private function nextContractNumber(int $companyId): string
    {
        $next = Contract::where('company_id', $companyId)->count() + 1;

        return 'CON-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
