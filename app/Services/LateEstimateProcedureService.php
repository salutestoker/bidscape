<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Mail\LateEstimateProcedureMail;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadStatusReminderRule;
use Illuminate\Support\Facades\Mail;

class LateEstimateProcedureService
{
    public function run(?Company $onlyCompany = null): int
    {
        $sent = 0;
        $companies = $onlyCompany ? Company::whereKey($onlyCompany->id) : Company::query();

        $companies->chunkById(100, function ($companies) use (&$sent): void {
            foreach ($companies as $company) {
                $sent += $this->runForCompany($company);
            }
        });

        return $sent;
    }

    public function runForCompany(Company $company): int
    {
        $procedure = $this->procedureFor($company);

        if ($procedure === null) {
            return 0;
        }

        $sent = 0;
        $now = now();

        Lead::query()
            ->where('company_id', $company->id)
            ->where('status', LeadStatus::PendingEstimate)
            ->whereNotNull('pending_estimate_started_at')
            ->where('pending_estimate_started_at', '<=', $now->copy()->subDays($procedure['days_allowed']))
            ->where(function ($query) use ($now): void {
                $query->whereNull('late_estimate_last_notified_at')
                    ->orWhereDate('late_estimate_last_notified_at', '<', $now->toDateString());
            })
            ->with(['source', 'estimates' => fn ($query) => $query->latest()])
            ->chunkById(100, function ($leads) use ($company, $procedure, $now, &$sent): void {
                foreach ($leads as $lead) {
                    $daysPendingEstimate = (int) floor($lead->pending_estimate_started_at->diffInDays($now));

                    Mail::to($procedure['contact_emails'])->queue(new LateEstimateProcedureMail(
                        lead: $lead,
                        company: $company,
                        daysAllowed: $procedure['days_allowed'],
                        daysPendingEstimate: $daysPendingEstimate,
                    ));

                    $lead->forceFill(['late_estimate_last_notified_at' => $now])->save();
                    $sent++;
                }
            });

        return $sent;
    }

    /**
     * @return array{days_allowed: int, contact_emails: array<int, string>}|null
     */
    private function procedureFor(Company $company): ?array
    {
        $rule = LeadStatusReminderRule::where('company_id', $company->id)
            ->where('lead_status', LeadStatus::PendingEstimate)
            ->with('recipients')
            ->first();

        if ($rule && $rule->is_enabled) {
            $daysAllowed = (int) ($rule->days_after_status ?? 0);
            $contactEmails = $rule->recipients
                ->pluck('email')
                ->map(fn (string $email): string => trim($email))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($daysAllowed >= 1 && $contactEmails !== []) {
                return [
                    'days_allowed' => $daysAllowed,
                    'contact_emails' => $contactEmails,
                ];
            }
        }

        $procedure = $company->settings['company_defaults']['late_estimate_procedure'] ?? null;

        if (! is_array($procedure)) {
            return null;
        }

        $daysAllowed = (int) ($procedure['days_allowed'] ?? 0);
        $contactEmails = collect($procedure['contact_emails'] ?? [])
            ->map(fn (mixed $email): string => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($daysAllowed < 1 || $contactEmails === []) {
            return null;
        }

        return [
            'days_allowed' => $daysAllowed,
            'contact_emails' => $contactEmails,
        ];
    }
}
