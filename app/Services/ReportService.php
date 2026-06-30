<?php

namespace App\Services;

use App\Enums\EstimateStatus;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Estimate;
use App\Models\Job;
use App\Models\Lead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(Company $company): array
    {
        $openLeadCount = Lead::where('company_id', $company->id)->active()->count();
        $activeEstimateCount = Estimate::where('company_id', $company->id)->active()->count();
        $contractValueThisMonth = Job::where('company_id', $company->id)
            ->whereMonth('contract_signed_at', now()->month)
            ->whereYear('contract_signed_at', now()->year)
            ->sum('contract_value_cents');
        $soldThisMonth = Job::where('company_id', $company->id)
            ->whereMonth('contract_signed_at', now()->month)
            ->whereYear('contract_signed_at', now()->year)
            ->count();
        $approved = Lead::where('company_id', $company->id)->where('status', LeadStatus::Approved)->count();
        $resolved = Lead::where('company_id', $company->id)->whereIn('status', [LeadStatus::Approved, LeadStatus::Closed])->count();

        return [
            'open_leads' => $openLeadCount,
            'active_estimates' => $activeEstimateCount,
            'sold_this_month' => $soldThisMonth,
            'contract_value_this_month_cents' => $contractValueThisMonth,
            'close_rate_basis_points' => $resolved > 0 ? intdiv($approved * 10000, $resolved) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function salesSummary(Company $company): array
    {
        $jobs = Job::query()
            ->where('company_id', $company->id)
            ->whereIn('status', [JobStatus::Sold, JobStatus::PacketReady, JobStatus::HandedOff])
            ->get();

        $byMonth = $jobs
            ->groupBy(fn (Job $job) => Carbon::parse($job->contract_signed_at)->format('M Y'))
            ->map(fn (Collection $items) => $items->sum('contract_value_cents'));

        $byCategory = DB::table('estimate_items')
            ->join('assemblies', 'assemblies.id', '=', 'estimate_items.assembly_id')
            ->join('estimates', 'estimates.id', '=', 'estimate_items.estimate_id')
            ->where('estimates.company_id', $company->id)
            ->where('estimates.status', EstimateStatus::Signed->value)
            ->select('assemblies.category', DB::raw('SUM(estimate_items.total_cents) as total_cents'), DB::raw('COUNT(*) as count'))
            ->groupBy('assemblies.category')
            ->orderByDesc('total_cents')
            ->get();

        return [
            'total_contract_value_cents' => $jobs->sum('contract_value_cents'),
            'average_contract_value_cents' => (int) round($jobs->avg('contract_value_cents') ?? 0),
            'contracts_won' => $jobs->count(),
            'by_month' => $byMonth,
            'by_category' => $byCategory,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leadSourceReport(Company $company): array
    {
        $rows = DB::table('lead_sources')
            ->leftJoin('leads', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->leftJoin('estimates', 'estimates.lead_id', '=', 'leads.id')
            ->leftJoin('project_jobs', 'project_jobs.lead_id', '=', 'leads.id')
            ->where('lead_sources.company_id', $company->id)
            ->select(
                'lead_sources.name',
                DB::raw('COUNT(DISTINCT leads.id) as leads_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN estimates.sent_at IS NOT NULL THEN estimates.id END) as estimates_sent_count'),
                DB::raw("SUM(CASE WHEN leads.status = 'approved' THEN 1 ELSE 0 END) as approved_count"),
                DB::raw('COUNT(DISTINCT project_jobs.id) as contracts_count'),
                DB::raw('SUM(COALESCE(project_jobs.contract_value_cents, 0)) as contract_value_cents'),
            )
            ->groupBy('lead_sources.id', 'lead_sources.name')
            ->orderByDesc('contract_value_cents')
            ->get()
            ->map(function ($row) {
                $row->conversion_basis_points = $row->leads_count > 0 ? intdiv((int) $row->approved_count * 10000, (int) $row->leads_count) : 0;

                return $row;
            });

        return ['rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    public function estimateConversion(Company $company): array
    {
        $created = Estimate::where('company_id', $company->id)->count();
        $sent = Estimate::where('company_id', $company->id)->whereNotNull('sent_at')->count();
        $approved = Estimate::where('company_id', $company->id)->whereNotNull('approved_at')->count();
        $declined = Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Declined)->count();
        $expired = Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Expired)->count();
        $signed = Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Signed)->count();
        $signedValue = Estimate::where('company_id', $company->id)->where('status', EstimateStatus::Signed)->sum('selling_price_cents');

        return [
            'created' => $created,
            'sent' => $sent,
            'approved' => $approved,
            'declined' => $declined,
            'expired' => $expired,
            'signed' => $signed,
            'conversion_basis_points' => $created > 0 ? intdiv($signed * 10000, $created) : 0,
            'signed_value_cents' => $signedValue,
        ];
    }
}
