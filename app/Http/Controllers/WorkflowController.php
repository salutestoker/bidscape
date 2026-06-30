<?php

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\DepositStatus;
use App\Enums\JobStatus;
use App\Http\Requests\RecordDepositRequest;
use App\Http\Requests\SignContractRequest;
use App\Models\Contract;
use App\Models\Deposit;
use App\Models\Job;
use App\Models\Lead;
use App\Services\ActivityLogger;
use App\Services\ContractSigningService;
use App\Services\LeadConversionService;
use Illuminate\Http\RedirectResponse;

class WorkflowController extends Controller
{
    public function convertLead(Lead $lead, LeadConversionService $converter): RedirectResponse
    {
        $estimate = $converter->convertToEstimate($lead);

        return to_route('estimates.builder', $estimate)->with('success', 'Lead converted to estimate.');
    }

    public function signContract(SignContractRequest $request, Contract $contract, ContractSigningService $signer): RedirectResponse
    {
        $job = $signer->sign($contract, $request->string('signature_name')->toString() ?: null);

        return to_route('jobs.packet', $job)->with('success', 'Contract signed and job packet created.');
    }

    public function recordDeposit(RecordDepositRequest $request, Job $job, ActivityLogger $activity): RedirectResponse
    {
        $deposit = Deposit::where('project_job_id', $job->id)->first();
        $deposit?->forceFill([
            'amount_cents' => $request->integer('amount_cents'),
            'status' => DepositStatus::Paid,
            'payment_method' => $request->string('payment_method')->toString(),
            'reference' => $request->string('reference')->toString(),
            'paid_at' => now(),
        ])->save();

        $job->forceFill([
            'deposit_status' => DepositStatus::Paid,
            'next_action' => 'Review Job Packet',
        ])->save();

        $activity->log($job->company_id, ActivityEvent::DepositRecorded, 'Deposit recorded for signed contract.', $job, $request->user()->id);

        return back()->with('success', 'Deposit recorded.');
    }

    public function markPacketReady(Job $job, ActivityLogger $activity): RedirectResponse
    {
        $job->packet?->forceFill([
            'status' => 'ready',
            'ready_at' => now(),
            'missing_requirements' => [],
        ])->save();

        $job->forceFill([
            'status' => JobStatus::PacketReady,
            'packet_ready_at' => now(),
            'next_action' => 'Hand Off to Operations',
        ])->save();

        $activity->log($job->company_id, ActivityEvent::PacketReady, 'Job packet marked ready for operations handoff.', $job);

        return back()->with('success', 'Job packet marked ready.');
    }
}
