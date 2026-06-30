<?php

namespace App\Http\Controllers;

use App\Enums\EstimateDeclineReasonType;
use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Services\CompanySettingsService;
use App\Services\EstimateAcceptanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class PublicEstimateReviewController extends Controller
{
    public function show(string $token, CompanySettingsService $settings): Response
    {
        $estimate = $this->estimateForToken($token);
        $estimate->loadMissing('company', 'lead.source', 'customer', 'items.assembly', 'items.material', 'paymentTerm');
        $settings->ensureDefaults($estimate->company);

        return Inertia::render('Public/EstimateReview', [
            'token' => $token,
            'estimate' => [
                'number' => $estimate->estimate_number,
                'project' => $estimate->project_name,
                'status' => $estimate->status->value,
                'status_label' => $estimate->status->label(),
                'sent_at' => $estimate->sent_at?->format('M j, Y'),
                'expires_at' => $estimate->public_token_expires_at?->format('M j, Y'),
                'scope_summary' => $estimate->scope_summary,
                'terms' => $estimate->terms,
                'summary' => $estimate->only(['material_cost_cents', 'labor_cost_cents', 'equipment_cost_cents', 'delivery_cost_cents', 'overhead_cents', 'profit_cents', 'selling_price_cents', 'gross_margin_basis_points']),
                'company' => $estimate->company->only(['name', 'email', 'phone', 'website', 'address', 'city', 'state', 'postal_code']),
                'lead' => $estimate->lead?->only(['name', 'email', 'phone', 'site_address', 'city', 'state', 'postal_code']),
                'customer' => $estimate->customer?->only(['name', 'email', 'phone', 'site_address', 'city', 'state', 'postal_code']),
                'items' => $estimate->items->map(fn ($item) => [
                    'name' => $item->name,
                    'subtitle' => $item->subtitle,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price_cents' => $item->unit_price_cents,
                    'total_cents' => $item->total_cents,
                    'notes' => $item->notes,
                ])->values(),
                'sections' => $settings->enabledSectionKeys($estimate->company, 'estimate'),
            ],
            'canRespond' => in_array($estimate->status, [EstimateStatus::Emailed, EstimateStatus::Approved, EstimateStatus::SignaturePending], true),
        ]);
    }

    public function approve(Request $request, string $token, EstimateAcceptanceService $acceptance): RedirectResponse
    {
        $estimate = $this->estimateForToken($token);

        $validated = $request->validate([
            'signature_name' => ['required', 'string', 'max:255'],
            'signature_email' => ['nullable', 'email', 'max:255'],
        ]);

        $acceptance->approve(
            $estimate,
            $validated['signature_name'],
            $validated['signature_email'] ?? null,
            $request->ip(),
        );

        return to_route('estimate-review.show', $token)->with('success', 'Estimate approved and signed.');
    }

    public function decline(Request $request, string $token, EstimateAcceptanceService $acceptance): RedirectResponse
    {
        $estimate = $this->estimateForToken($token);

        $validated = $request->validate([
            'decline_reason_type' => ['required', new Enum(EstimateDeclineReasonType::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $acceptance->decline(
            $estimate,
            EstimateDeclineReasonType::from($validated['decline_reason_type']),
            $validated['reason'] ?? null,
        );

        return to_route('estimate-review.show', $token)->with('success', 'Estimate declined.');
    }

    private function estimateForToken(string $token): Estimate
    {
        $estimate = Estimate::where('public_token_hash', hash('sha256', $token))->firstOrFail();

        if ($estimate->public_token_expires_at && $estimate->public_token_expires_at->isPast()) {
            abort(410, 'This estimate review link has expired.');
        }

        return $estimate;
    }
}
