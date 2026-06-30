<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Enums\MaterialType;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\Company;
use App\Models\CompanyNotificationSetting;
use App\Models\DocumentTemplate;
use App\Models\LeadSource;
use App\Models\LeadStatusReminderRule;
use App\Models\Material;
use App\Services\CompanySettingsService;
use App\Services\MoneyCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly CompanySettingsService $settings)
    {
    }

    public function index(): Response
    {
        $this->settings->ensureDefaults($this->company());

        return Inertia::render('Settings/Index', [
            'sections' => [
                [
                    'key' => 'company_profile',
                    'title' => 'Company Profile',
                    'description' => 'Company information, logo, and branding.',
                    'href' => route('settings.company-profile'),
                    'disabled' => false,
                ],
                [
                    'key' => 'company_defaults',
                    'title' => 'Company Defaults',
                    'description' => 'Estimate templates, packet defaults, markups, and lead sources.',
                    'href' => route('settings.company-defaults'),
                    'disabled' => false,
                ],
                [
                    'key' => 'price_sheet',
                    'title' => 'Price Sheet',
                    'description' => 'Manage sellable line items, costs, units, and markup.',
                    'href' => route('settings.price-sheet'),
                    'disabled' => false,
                ],
                [
                    'key' => 'notification_settings',
                    'title' => 'Notification Settings',
                    'description' => 'Company digest emails and sales follow-up reminders.',
                    'href' => route('settings.notifications'),
                    'disabled' => false,
                ],
                [
                    'key' => 'import_price_sheet',
                    'title' => 'Import Price Sheet',
                    'description' => 'Upload PDF, XLSM, or CSV price sheets in the next phase.',
                    'href' => null,
                    'disabled' => true,
                ],
            ],
        ]);
    }

    public function companyProfile(): Response
    {
        $company = $this->company();
        $this->settings->ensureDefaults($company);

        return Inertia::render('Settings/CompanyProfile', [
            'company' => $this->settings->companyProfilePayload($company->refresh()),
        ]);
    }

    public function updateCompanyProfile(Request $request): RedirectResponse
    {
        $company = $this->company();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'contractor_license_number' => ['nullable', 'string', 'max:80'],
            'brand_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($validated['logo']);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->forceFill($validated)->save();

        return back()->with('success', 'Company profile updated.');
    }

    public function companyLogo(Company $company): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless((int) $company->id === (int) $this->company()->id, 404);
        abort_unless($company->logo_path && Storage::disk('public')->exists($company->logo_path), 404);

        return ResponseFactory::make(Storage::disk('public')->get($company->logo_path), 200, [
            'Content-Type' => Storage::disk('public')->mimeType($company->logo_path) ?: 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function companyDefaults(): Response
    {
        $company = $this->company();
        $this->settings->ensureDefaults($company);

        return Inertia::render('Settings/CompanyDefaults', [
            'company' => [
                'default_price_sheet_markup_basis_points' => $company->refresh()->default_price_sheet_markup_basis_points,
            ],
            'estimateTemplate' => $this->settings->templatePayload($this->settings->templateFor($company, 'estimate')),
            'jobPacketTemplate' => $this->settings->templatePayload($this->settings->templateFor($company, 'job_packet')),
            'estimateSectionDefinitions' => $this->settings->estimateSections(),
            'jobPacketSectionDefinitions' => $this->settings->jobPacketSections(),
            'leadSources' => LeadSource::where('company_id', $company->id)
                ->orderBy('is_active', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'channel', 'is_active']),
        ]);
    }

    public function updateCompanyDefaults(Request $request): RedirectResponse
    {
        $company = $this->company();
        $this->settings->ensureDefaults($company);

        $validated = $request->validate([
            'default_price_sheet_markup_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'estimate_template' => ['required', 'array'],
            'estimate_template.terms_text' => ['nullable', 'string'],
            'estimate_template.email_subject' => ['nullable', 'string', 'max:255'],
            'estimate_template.email_body' => ['nullable', 'string', 'max:2000'],
            'estimate_template.sections' => ['required', 'array'],
            'estimate_template.sections.*.key' => ['required', Rule::in(array_column($this->settings->estimateSections(), 'key'))],
            'estimate_template.sections.*.enabled' => ['required', 'boolean'],
            'job_packet_template' => ['required', 'array'],
            'job_packet_template.sections' => ['required', 'array'],
            'job_packet_template.sections.*.key' => ['required', Rule::in(array_column($this->settings->jobPacketSections(), 'key'))],
            'job_packet_template.sections.*.enabled' => ['required', 'boolean'],
            'job_packet_template.recipients' => ['nullable', 'array', 'max:20'],
            'job_packet_template.recipients.*' => ['nullable', 'email', 'max:255'],
        ]);

        $company->forceFill([
            'default_price_sheet_markup_basis_points' => $validated['default_price_sheet_markup_basis_points'],
        ])->save();

        $this->updateTemplate(
            $this->settings->templateFor($company, 'estimate'),
            $validated['estimate_template'],
            $this->settings->estimateSections(),
        );

        $jobPacketTemplate = $this->settings->templateFor($company, 'job_packet');
        $this->updateTemplate(
            $jobPacketTemplate,
            $validated['job_packet_template'],
            $this->settings->jobPacketSections(),
        );
        $this->settings->replaceTemplateRecipients(
            $jobPacketTemplate,
            collect($validated['job_packet_template']['recipients'] ?? []),
        );

        return back()->with('success', 'Company defaults updated.');
    }

    public function storeLeadSource(Request $request): RedirectResponse
    {
        $company = $this->company();
        $validated = $this->validateLeadSource($request, $company);

        LeadSource::create([
            ...$validated,
            'company_id' => $company->id,
        ]);

        return back()->with('success', 'Lead source created.');
    }

    public function updateLeadSource(Request $request, LeadSource $source): RedirectResponse
    {
        $this->ensureCompany($source);
        $source->forceFill($this->validateLeadSource($request, $source->company))->save();

        return back()->with('success', 'Lead source updated.');
    }

    public function priceSheet(Request $request): Response
    {
        $company = $this->company();
        $this->settings->ensureDefaults($company);

        $query = Material::where('company_id', $company->id);
        $this->applySearch($query, $request, ['name', 'sku', 'category', 'vendor']);
        $this->applySort($query, $request, [
            'name' => 'name',
            'type_label' => 'type',
            'category' => 'category',
            'unit' => 'unit',
            'unit_cost_cents' => 'unit_cost_cents',
            'markup_basis_points' => 'markup_basis_points',
            'selling_price_cents' => 'selling_price_cents',
        ], 'name', 'asc');

        return Inertia::render('Settings/PriceSheet', [
            'materials' => $query->paginate(8)->withQueryString()->through(fn (Material $material) => $this->materialRow($material)),
            'types' => collect(MaterialType::cases())->map(fn (MaterialType $type) => ['value' => $type->value, 'label' => $type->label()]),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'defaultMarkupBasisPoints' => $company->refresh()->default_price_sheet_markup_basis_points,
        ]);
    }

    public function storeMaterial(StoreMaterialRequest $request): RedirectResponse
    {
        Material::create([
            ...$this->materialPayload($request),
            'company_id' => $this->company()->id,
        ]);

        return back()->with('success', 'Price sheet item created.');
    }

    public function updateMaterial(StoreMaterialRequest $request, Material $material): RedirectResponse
    {
        $this->ensureCompany($material);
        $material->forceFill($this->materialPayload($request))->save();

        return back()->with('success', 'Price sheet item updated.');
    }

    public function notifications(): Response
    {
        $company = $this->company();

        return Inertia::render('Settings/Notifications', [
            'notificationSettings' => $this->settings->notificationPayload($company),
            'leadStatuses' => $this->settings->leadStatusOptions(),
            'digestFrequencyOptions' => [
                ['value' => 'off', 'label' => 'Off'],
                ['value' => 'daily', 'label' => 'Daily'],
                ['value' => 'weekly', 'label' => 'Weekly'],
                ['value' => 'monthly', 'label' => 'Monthly'],
            ],
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $company = $this->company();
        $this->settings->ensureDefaults($company);

        $validated = $request->validate([
            'digest' => ['required', 'array'],
            'digest.digest_frequency' => ['required', Rule::in(['off', 'daily', 'weekly', 'monthly'])],
            'digest.include_pipeline_summary' => ['required', 'boolean'],
            'digest.include_late_estimates' => ['required', 'boolean'],
            'digest.include_recent_activity' => ['required', 'boolean'],
            'digest.include_sales_summary' => ['required', 'boolean'],
            'rules' => ['required', 'array'],
            'rules.*.lead_status' => ['required', new Enum(LeadStatus::class)],
            'rules.*.is_enabled' => ['required', 'boolean'],
            'rules.*.days_after_status' => ['nullable', 'integer', 'min:1', 'max:365'],
            'rules.*.recipients' => ['nullable', 'array', 'max:20'],
            'rules.*.recipients.*' => ['nullable', 'email', 'max:255'],
        ]);

        $this->validateReminderRules($validated['rules']);

        CompanyNotificationSetting::where('company_id', $company->id)
            ->firstOrFail()
            ->forceFill($validated['digest'])
            ->save();

        foreach ($validated['rules'] as $rulePayload) {
            $rule = LeadStatusReminderRule::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'lead_status' => $rulePayload['lead_status'],
                ],
                [
                    'is_enabled' => (bool) $rulePayload['is_enabled'],
                    'days_after_status' => filled($rulePayload['days_after_status'] ?? null)
                        ? (int) $rulePayload['days_after_status']
                        : null,
                ],
            );

            $this->settings->replaceRuleRecipients($rule, collect($rulePayload['recipients'] ?? []));
        }

        return back()->with('success', 'Notification settings updated.');
    }

    private function company(): Company
    {
        return request()->user()?->company ?: Company::firstOrFail();
    }

    private function ensureCompany(object $model): void
    {
        abort_unless((int) $model->company_id === (int) $this->company()->id, 404);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array{key: string, label: string, description: string, enabled: bool}>  $definitions
     */
    private function updateTemplate(DocumentTemplate $template, array $payload, array $definitions): void
    {
        $template->forceFill([
            'terms_text' => $payload['terms_text'] ?? $template->terms_text,
            'email_subject' => $payload['email_subject'] ?? null,
            'email_body' => $payload['email_body'] ?? null,
        ])->save();

        $labels = collect($definitions)->mapWithKeys(fn (array $definition): array => [
            $definition['key'] => $definition['label'],
        ]);

        collect($payload['sections'])
            ->values()
            ->each(function (array $section, int $index) use ($template, $labels): void {
                $template->sections()->updateOrCreate(
                    ['section_key' => $section['key']],
                    [
                        'label' => $labels[$section['key']],
                        'enabled' => (bool) $section['enabled'],
                        'sort_order' => $index + 1,
                    ],
                );
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLeadSource(Request $request, Company $company): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lead_sources', 'name')
                    ->where('company_id', $company->id)
                    ->ignore($request->route('source')),
            ],
            'channel' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rules
     *
     * @throws ValidationException
     */
    private function validateReminderRules(array $rules): void
    {
        foreach ($rules as $index => $rule) {
            if (! (bool) $rule['is_enabled']) {
                continue;
            }

            $recipients = collect($rule['recipients'] ?? [])
                ->map(fn (mixed $email): string => trim((string) $email))
                ->filter();

            if (blank($rule['days_after_status'] ?? null)) {
                throw ValidationException::withMessages([
                    "rules.{$index}.days_after_status" => 'Enter the number of days before this reminder is sent.',
                ]);
            }

            if ($recipients->isEmpty()) {
                throw ValidationException::withMessages([
                    "rules.{$index}.recipients" => 'Add at least one recipient or disable this reminder.',
                ]);
            }
        }
    }

    private function applySearch(Builder $query, Request $request, array $columns): void
    {
        $search = trim((string) $request->query('search', ''));

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $query) use ($columns, $search): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    private function applySort(Builder $query, Request $request, array $allowed, string $defaultSort, string $defaultDirection = 'asc'): void
    {
        $sort = (string) $request->query('sort', $defaultSort);
        $direction = strtolower((string) $request->query('direction', $defaultDirection)) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($allowed[$sort] ?? $allowed[$defaultSort], $direction);
    }

    /**
     * @return array<string, mixed>
     */
    private function materialPayload(StoreMaterialRequest $request): array
    {
        $validated = $request->validated();
        $validated['selling_price_cents'] = MoneyCalculator::priceForMarkup(
            (int) $validated['unit_cost_cents'],
            (int) $validated['markup_basis_points'],
        );
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function materialRow(Material $material): array
    {
        return [
            'id' => $material->id,
            'name' => $material->name,
            'type' => $material->type->value,
            'type_label' => $material->type->label(),
            'sku' => $material->sku,
            'category' => $material->category,
            'unit' => $material->unit,
            'unit_cost_cents' => $material->unit_cost_cents,
            'markup_basis_points' => $material->markup_basis_points,
            'selling_price_cents' => $material->selling_price_cents,
            'hourly_rate_cents' => $material->hourly_rate_cents,
            'minimum_charge_cents' => $material->minimum_charge_cents,
            'pricing_method' => $material->pricing_method,
            'vendor' => $material->vendor,
            'description' => $material->description,
            'notes' => $material->notes,
            'is_active' => $material->is_active,
            'photo_path' => $material->photo_path,
        ];
    }
}
