<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\CompanyNotificationSetting;
use App\Models\DocumentTemplate;
use App\Models\LeadStatusReminderRule;
use Illuminate\Support\Collection;

class CompanySettingsService
{
    public const DEFAULT_ESTIMATE_TERMS = 'Estimate valid for 30 days. Deposit is due after signature.';

    /**
     * @return array<int, array{key: string, label: string, description: string, enabled: bool}>
     */
    public function estimateSections(): array
    {
        return [
            ['key' => 'header', 'label' => 'Header', 'description' => 'Company, estimate number, project, and sent date.', 'enabled' => true],
            ['key' => 'prepared_for', 'label' => 'Prepared For', 'description' => 'Customer or lead contact details.', 'enabled' => true],
            ['key' => 'project_site', 'label' => 'Project Site', 'description' => 'Site address and location details.', 'enabled' => true],
            ['key' => 'scope_summary', 'label' => 'Scope Summary', 'description' => 'Project summary text from the estimate.', 'enabled' => true],
            ['key' => 'scope_items', 'label' => 'Scope Items', 'description' => 'Line items, quantities, units, and totals.', 'enabled' => true],
            ['key' => 'price_summary', 'label' => 'Price Summary', 'description' => 'Direct cost, overhead, profit, and selling price.', 'enabled' => true],
            ['key' => 'terms', 'label' => 'Terms', 'description' => 'Company terms and conditions.', 'enabled' => true],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, description: string, enabled: bool}>
     */
    public function jobPacketSections(): array
    {
        return [
            ['key' => 'header', 'label' => 'Header', 'description' => 'Company, packet number, and project name.', 'enabled' => true],
            ['key' => 'overview', 'label' => 'Overview', 'description' => 'Customer and site handoff details.', 'enabled' => true],
            ['key' => 'materials_scope', 'label' => 'Materials And Scope', 'description' => 'Accepted scope items retained in the snapshot.', 'enabled' => true],
            ['key' => 'commercial_summary', 'label' => 'Commercial Summary', 'description' => 'Contract value and signed date.', 'enabled' => true],
        ];
    }

    public function ensureDefaults(Company $company): void
    {
        $legacy = $company->settings ?? [];

        $updates = [];
        if (blank($company->default_price_sheet_markup_basis_points)) {
            $updates['default_price_sheet_markup_basis_points'] = 3000;
        }

        if ($updates !== []) {
            $company->forceFill($updates)->save();
            $company->refresh();
        }

        $estimateTemplate = $this->templateFor($company, 'estimate');
        if (blank($estimateTemplate->terms_text) && ! empty($legacy['estimate_terms'])) {
            $estimateTemplate->forceFill(['terms_text' => $legacy['estimate_terms']])->save();
        }

        $this->ensureSections($estimateTemplate, $this->estimateSections());
        $this->ensureSections($this->templateFor($company, 'job_packet'), $this->jobPacketSections());

        CompanyNotificationSetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'digest_frequency' => 'off',
                'include_pipeline_summary' => true,
                'include_late_estimates' => true,
                'include_recent_activity' => true,
                'include_sales_summary' => true,
            ],
        );

        $this->ensureReminderRules($company);
    }

    public function templateFor(Company $company, string $type): DocumentTemplate
    {
        $legacy = $company->settings ?? [];

        return DocumentTemplate::firstOrCreate(
            ['company_id' => $company->id, 'type' => $type],
            [
                'name' => $type === 'estimate' ? 'Default Estimate' : 'Default Job Packet',
                'terms_text' => $type === 'estimate'
                    ? ($legacy['estimate_terms'] ?? self::DEFAULT_ESTIMATE_TERMS)
                    : null,
                'email_subject' => null,
                'email_body' => $type === 'estimate' ? 'Your estimate is ready for review.' : null,
                'is_default' => true,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function companyProfilePayload(Company $company): array
    {
        return [
            ...$company->only([
                'id',
                'name',
                'industry',
                'email',
                'phone',
                'website',
                'address',
                'city',
                'state',
                'postal_code',
                'contractor_license_number',
                'logo_path',
                'brand_primary_color',
            ]),
            'logo_url' => $company->logo_path ? route('settings.company-logo', $company) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function templatePayload(DocumentTemplate $template): array
    {
        $template->loadMissing('sections', 'recipients');

        return [
            'id' => $template->id,
            'type' => $template->type,
            'name' => $template->name,
            'terms_text' => $template->terms_text,
            'email_subject' => $template->email_subject,
            'email_body' => $template->email_body,
            'recipients' => $template->recipients->pluck('email')->values()->all(),
            'sections' => $template->sections->map(fn ($section): array => [
                'key' => $section->section_key,
                'label' => $section->label,
                'enabled' => $section->enabled,
                'sort_order' => $section->sort_order,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function enabledSectionKeys(Company $company, string $type): array
    {
        $this->ensureDefaults($company);

        return $this->templateFor($company, $type)
            ->sections()
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->pluck('section_key')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function leadStatusOptions(): array
    {
        return collect(LeadStatus::cases())
            ->map(fn (LeadStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function notificationPayload(Company $company): array
    {
        $this->ensureDefaults($company);

        $settings = CompanyNotificationSetting::where('company_id', $company->id)->firstOrFail();
        $rules = LeadStatusReminderRule::where('company_id', $company->id)
            ->with('recipients')
            ->get()
            ->keyBy(fn (LeadStatusReminderRule $rule): string => $rule->lead_status->value);

        return [
            'digest' => $settings->only([
                'digest_frequency',
                'include_pipeline_summary',
                'include_late_estimates',
                'include_recent_activity',
                'include_sales_summary',
            ]),
            'rules' => collect(LeadStatus::cases())->map(function (LeadStatus $status) use ($rules): array {
                $rule = $rules->get($status->value);

                return [
                    'lead_status' => $status->value,
                    'lead_status_label' => $status->label(),
                    'is_enabled' => (bool) $rule?->is_enabled,
                    'days_after_status' => $rule?->days_after_status,
                    'recipients' => $rule?->recipients->pluck('email')->values()->all() ?? [],
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<int, array{key: string, label: string, description?: string, enabled: bool}>  $definitions
     */
    private function ensureSections(DocumentTemplate $template, array $definitions): void
    {
        $template->loadMissing('sections');
        $existing = $template->sections->keyBy('section_key');

        foreach ($definitions as $index => $definition) {
            if ($existing->has($definition['key'])) {
                continue;
            }

            $template->sections()->create([
                'section_key' => $definition['key'],
                'label' => $definition['label'],
                'enabled' => $definition['enabled'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function ensureReminderRules(Company $company): void
    {
        $legacy = $company->settings['company_defaults']['late_estimate_procedure'] ?? [];
        $legacyDays = (int) ($legacy['days_allowed'] ?? 0);
        $legacyEmails = collect($legacy['contact_emails'] ?? [])
            ->map(fn (mixed $email): string => trim((string) $email))
            ->filter()
            ->unique()
            ->values();

        foreach (LeadStatus::cases() as $status) {
            $rule = LeadStatusReminderRule::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'lead_status' => $status,
                ],
                [
                    'is_enabled' => false,
                    'days_after_status' => null,
                ],
            );

            if (
                $status === LeadStatus::PendingEstimate
                && ! $rule->is_enabled
                && blank($rule->days_after_status)
                && $rule->recipients()->count() === 0
                && $legacyDays > 0
                && $legacyEmails->isNotEmpty()
            ) {
                $rule->forceFill([
                    'is_enabled' => true,
                    'days_after_status' => $legacyDays,
                ])->save();

                $this->replaceRuleRecipients($rule, $legacyEmails);
            }
        }
    }

    /**
     * @param  Collection<int, string>|array<int, string>  $emails
     */
    public function replaceRuleRecipients(LeadStatusReminderRule $rule, Collection|array $emails): void
    {
        $rule->recipients()->delete();

        collect($emails)
            ->map(fn (string $email): string => trim($email))
            ->filter()
            ->unique()
            ->values()
            ->each(fn (string $email, int $index) => $rule->recipients()->create([
                'email' => $email,
                'sort_order' => $index + 1,
            ]));
    }

    /**
     * @param  Collection<int, string>|array<int, string>  $emails
     */
    public function replaceTemplateRecipients(DocumentTemplate $template, Collection|array $emails): void
    {
        $template->recipients()->delete();

        collect($emails)
            ->map(fn (string $email): string => trim($email))
            ->filter()
            ->unique()
            ->values()
            ->each(fn (string $email, int $index) => $template->recipients()->create([
                'email' => $email,
                'sort_order' => $index + 1,
            ]));
    }
}
