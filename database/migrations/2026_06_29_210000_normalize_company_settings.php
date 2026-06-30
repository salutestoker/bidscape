<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contractor_license_number')->nullable();
            $table->string('brand_primary_color', 16)->nullable();
            $table->unsignedInteger('default_price_sheet_markup_basis_points')->default(3000);
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('name');
            $table->text('terms_text')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'type']);
        });

        Schema::create('document_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 80);
            $table->string('label');
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['document_template_id', 'section_key'], 'doc_tpl_sections_tpl_key_unique');
            $table->index(['document_template_id', 'sort_order'], 'doc_tpl_sections_tpl_order_idx');
        });

        Schema::create('document_template_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['document_template_id', 'sort_order'], 'doc_tpl_recip_tpl_order_idx');
        });

        Schema::create('company_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('digest_frequency', 40)->default('off');
            $table->boolean('include_pipeline_summary')->default(true);
            $table->boolean('include_late_estimates')->default(true);
            $table->boolean('include_recent_activity')->default(true);
            $table->boolean('include_sales_summary')->default(true);
            $table->timestamps();
            $table->unique('company_id');
        });

        Schema::create('lead_status_reminder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('lead_status', 80);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedSmallInteger('days_after_status')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'lead_status']);
        });

        Schema::create('lead_status_reminder_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_status_reminder_rule_id');
            $table->string('email');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->foreign('lead_status_reminder_rule_id', 'lsrr_rule_fk')
                ->references('id')
                ->on('lead_status_reminder_rules')
                ->cascadeOnDelete();
            $table->index(['lead_status_reminder_rule_id', 'sort_order'], 'lsrr_rule_order_idx');
        });

        $this->backfillCompanies();
        $this->recalculateMaterialSellingPrices();
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_reminder_recipients');
        Schema::dropIfExists('lead_status_reminder_rules');
        Schema::dropIfExists('company_notification_settings');
        Schema::dropIfExists('document_template_recipients');
        Schema::dropIfExists('document_template_sections');
        Schema::dropIfExists('document_templates');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'contractor_license_number',
                'brand_primary_color',
                'default_price_sheet_markup_basis_points',
            ]);
        });
    }

    private function backfillCompanies(): void
    {
        DB::table('companies')->orderBy('id')->chunkById(100, function ($companies): void {
            foreach ($companies as $company) {
                $settings = $this->decodeSettings($company->settings ?? null);
                $brandColor = $settings['company_profile']['brand_color'] ?? null;

                if (is_string($brandColor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $brandColor)) {
                    DB::table('companies')
                        ->where('id', $company->id)
                        ->update(['brand_primary_color' => $brandColor]);
                }

                $estimateTemplateId = $this->insertTemplate(
                    companyId: (int) $company->id,
                    type: 'estimate',
                    name: 'Default Estimate',
                    termsText: $settings['estimate_terms'] ?? 'Estimate valid for 30 days. Deposit is due after signature.',
                    emailSubject: null,
                    emailBody: 'Your estimate is ready for review.',
                );
                $this->insertSections($estimateTemplateId, $this->estimateSections());

                $jobPacketTemplateId = $this->insertTemplate(
                    companyId: (int) $company->id,
                    type: 'job_packet',
                    name: 'Default Job Packet',
                    termsText: null,
                    emailSubject: null,
                    emailBody: null,
                );
                $this->insertSections($jobPacketTemplateId, $this->jobPacketSections());

                DB::table('company_notification_settings')->insertOrIgnore([
                    'company_id' => $company->id,
                    'digest_frequency' => 'off',
                    'include_pipeline_summary' => true,
                    'include_late_estimates' => true,
                    'include_recent_activity' => true,
                    'include_sales_summary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->insertReminderRules((int) $company->id, $settings);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSettings(mixed $settings): array
    {
        if (is_array($settings)) {
            return $settings;
        }

        if (! is_string($settings) || $settings === '') {
            return [];
        }

        $decoded = json_decode($settings, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function insertTemplate(
        int $companyId,
        string $type,
        string $name,
        ?string $termsText,
        ?string $emailSubject,
        ?string $emailBody,
    ): int {
        $existing = DB::table('document_templates')
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) DB::table('document_templates')->insertGetId([
            'company_id' => $companyId,
            'type' => $type,
            'name' => $name,
            'terms_text' => $termsText,
            'email_subject' => $emailSubject,
            'email_body' => $emailBody,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array{key: string, label: string, enabled: bool}>  $sections
     */
    private function insertSections(int $templateId, array $sections): void
    {
        foreach ($sections as $index => $section) {
            DB::table('document_template_sections')->insertOrIgnore([
                'document_template_id' => $templateId,
                'section_key' => $section['key'],
                'label' => $section['label'],
                'enabled' => $section['enabled'],
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return array<int, array{key: string, label: string, enabled: bool}>
     */
    private function estimateSections(): array
    {
        return [
            ['key' => 'header', 'label' => 'Header', 'enabled' => true],
            ['key' => 'prepared_for', 'label' => 'Prepared For', 'enabled' => true],
            ['key' => 'project_site', 'label' => 'Project Site', 'enabled' => true],
            ['key' => 'scope_summary', 'label' => 'Scope Summary', 'enabled' => true],
            ['key' => 'scope_items', 'label' => 'Scope Items', 'enabled' => true],
            ['key' => 'price_summary', 'label' => 'Price Summary', 'enabled' => true],
            ['key' => 'terms', 'label' => 'Terms', 'enabled' => true],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, enabled: bool}>
     */
    private function jobPacketSections(): array
    {
        return [
            ['key' => 'header', 'label' => 'Header', 'enabled' => true],
            ['key' => 'overview', 'label' => 'Overview', 'enabled' => true],
            ['key' => 'materials_scope', 'label' => 'Materials And Scope', 'enabled' => true],
            ['key' => 'commercial_summary', 'label' => 'Commercial Summary', 'enabled' => true],
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function insertReminderRules(int $companyId, array $settings): void
    {
        $legacyProcedure = $settings['company_defaults']['late_estimate_procedure'] ?? [];
        $legacyDays = (int) ($legacyProcedure['days_allowed'] ?? 0);
        $legacyEmails = collect($legacyProcedure['contact_emails'] ?? [])
            ->map(fn (mixed $email): string => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ([
            'pending_contact',
            'site_visit',
            'pending_estimate',
            'estimate_sent',
            'approved',
            'closed',
        ] as $status) {
            $enabled = $status === 'pending_estimate' && $legacyDays > 0 && $legacyEmails !== [];
            $ruleId = (int) DB::table('lead_status_reminder_rules')->insertGetId([
                'company_id' => $companyId,
                'lead_status' => $status,
                'is_enabled' => $enabled,
                'days_after_status' => $enabled ? $legacyDays : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($enabled) {
                foreach ($legacyEmails as $index => $email) {
                    DB::table('lead_status_reminder_recipients')->insert([
                        'lead_status_reminder_rule_id' => $ruleId,
                        'email' => $email,
                        'sort_order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function recalculateMaterialSellingPrices(): void
    {
        DB::table('materials')->orderBy('id')->chunkById(100, function ($materials): void {
            foreach ($materials as $material) {
                $cost = (int) $material->unit_cost_cents;
                $markup = (int) $material->markup_basis_points;
                $markupCents = intdiv(($cost * $markup) + 5000, 10000);

                DB::table('materials')
                    ->where('id', $material->id)
                    ->update(['selling_price_cents' => $cost + $markupCents]);
            }
        });
    }
};
