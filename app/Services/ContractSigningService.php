<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ContractStatus;
use App\Enums\DepositStatus;
use App\Enums\EstimateStatus;
use App\Enums\JobStatus;
use App\Models\Contract;
use App\Models\Deposit;
use App\Models\Job;
use App\Models\JobPacket;
use Illuminate\Support\Facades\DB;

class ContractSigningService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly EstimateSnapshotService $snapshots,
        private readonly LeadStatusWorkflow $leadStatuses,
    ) {}

    public function sign(Contract $contract, ?string $signatureName = null): Job
    {
        return DB::transaction(function () use ($contract, $signatureName): Job {
            $contract->loadMissing('customer', 'estimate.lead', 'estimate.customer', 'estimate.paymentTerm');

            if ($existing = $contract->job) {
                return $existing;
            }

            $snapshot = $this->snapshots->snapshot($contract->estimate);

            $contract->forceFill([
                'status' => ContractStatus::Signed,
                'signature_name' => $signatureName ?: $contract->customer->name,
                'signature_email' => $contract->customer->email,
                'signed_at' => now(),
                'accepted_snapshot' => $snapshot,
            ])->save();

            $contract->estimate->forceFill([
                'status' => EstimateStatus::Signed,
                'accepted_snapshot' => $snapshot,
                'approved_at' => $contract->estimate->approved_at ?: now(),
                'signed_at' => $contract->estimate->signed_at ?: now(),
                'signature_name' => $contract->estimate->signature_name ?: $contract->signature_name,
                'signature_email' => $contract->estimate->signature_email ?: $contract->signature_email,
            ])->save();

            if ($contract->estimate->lead) {
                $this->leadStatuses->markApproved($contract->estimate->lead);
            }

            $job = Job::create([
                'company_id' => $contract->company_id,
                'customer_id' => $contract->customer_id,
                'lead_id' => $contract->estimate->lead_id,
                'estimate_id' => $contract->estimate_id,
                'contract_id' => $contract->id,
                'job_number' => $this->nextJobNumber($contract->company_id),
                'project_name' => $contract->estimate->project_name,
                'status' => JobStatus::Sold,
                'contract_value_cents' => $contract->total_cents,
                'deposit_status' => DepositStatus::Pending,
                'next_action' => 'Confirm Deposit',
                'site_address' => $contract->customer->site_address,
                'site_notes' => $contract->estimate->lead?->site_notes,
                'contract_signed_at' => now(),
                'accepted_snapshot' => $snapshot,
            ]);

            $depositAmount = MoneyCalculator::percent(
                $contract->total_cents,
                $contract->estimate->paymentTerm?->deposit_basis_points ?? 5000,
            );

            Deposit::create([
                'company_id' => $contract->company_id,
                'customer_id' => $contract->customer_id,
                'contract_id' => $contract->id,
                'project_job_id' => $job->id,
                'amount_cents' => $depositAmount,
                'status' => DepositStatus::Pending,
                'due_at' => now()->addDays(7),
            ]);

            JobPacket::create([
                'company_id' => $contract->company_id,
                'project_job_id' => $job->id,
                'contract_id' => $contract->id,
                'packet_number' => 'PKT-'.substr($job->job_number, 4),
                'status' => 'draft',
                'snapshot' => $snapshot,
                'missing_requirements' => ['deposit', 'photos'],
            ]);

            $this->activity->log($contract->company_id, ActivityEvent::ContractSigned, 'Contract signed and accepted estimate snapshot frozen.', $contract);
            $this->activity->log($contract->company_id, ActivityEvent::JobCreated, 'Sold project and job packet shell created.', $job);

            return $job->refresh();
        });
    }

    private function nextJobNumber(int $companyId): string
    {
        $next = Job::where('company_id', $companyId)->count() + 1;

        return 'JOB-'.now()->format('Y').'-'.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
