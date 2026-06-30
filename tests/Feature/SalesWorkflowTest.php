<?php

namespace Tests\Feature;

use App\Enums\ActivityEvent;
use App\Enums\AttachmentType;
use App\Enums\ContractStatus;
use App\Enums\DepositStatus;
use App\Enums\EstimateDeclineReasonType;
use App\Enums\EstimateStatus;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\MaterialType;
use App\Mail\EstimateSentMail;
use App\Mail\LateEstimateProcedureMail;
use App\Models\Assembly;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DocumentTemplate;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatusReminderRule;
use App\Models\Material;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Services\AssemblyFormulaEvaluator;
use App\Services\EstimateCalculator;
use App\Services\EstimateSendService;
use App\Services\JobPacketPdfService;
use App\Services\LateEstimateProcedureService;
use App\Services\LeadConversionService;
use App\Services\LeadStatusWorkflow;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class SalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_proposals_are_absent_from_the_workflow_schema_and_models(): void
    {
        $this->assertFalse(Schema::hasTable('proposals'));
        $this->assertFalse(class_exists('App\\Models\\Proposal', false));
    }

    public function test_lead_conversion_creates_draft_estimate_without_customer_creation(): void
    {
        $company = Company::factory()->create();
        $source = LeadSource::create([
            'company_id' => $company->id,
            'name' => 'Website',
            'channel' => 'web',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $this->makePaymentTerm($company);

        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'lead_source_id' => $source->id,
            'assigned_user_id' => $user->id,
            'customer_id' => null,
            'name' => 'Jordan Client',
            'status' => LeadStatus::PendingContact,
            'requested_project_specifications' => 'Turf, pavers, and lighting.',
        ]);

        $estimate = app(LeadConversionService::class)->convertToEstimate($lead, 'Backyard Renovation');

        $this->assertSame($company->id, $estimate->company_id);
        $this->assertNull($estimate->customer_id);
        $this->assertSame($lead->id, $estimate->lead_id);
        $this->assertSame(EstimateStatus::Draft, $estimate->status);
        $this->assertSame(0, Customer::count());
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => LeadStatus::PendingEstimate->value,
            'customer_id' => null,
        ]);
        $this->assertNotNull($lead->refresh()->pending_estimate_started_at);
        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $company->id,
            'event' => ActivityEvent::LeadConverted->value,
        ]);
    }

    public function test_new_lead_without_site_visit_date_is_pending_contact(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $source = LeadSource::create([
            'company_id' => $company->id,
            'name' => 'Website',
        ]);

        $this->actingAs($user)->post('/leads', [
            'name' => 'Avery Homeowner',
            'phone' => '480-555-0199',
            'email' => 'avery@example.test',
            'lead_source_id' => $source->id,
            'site_address' => '456 E Test Ave',
            'city' => 'Mesa',
            'state' => 'AZ',
            'status' => LeadStatus::Closed->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'company_id' => $company->id,
            'name' => 'Avery Homeowner',
            'status' => LeadStatus::PendingContact->value,
            'site_visit_scheduled_at' => null,
        ]);
    }

    public function test_new_lead_with_future_site_visit_datetime_is_site_visit(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $source = LeadSource::create([
            'company_id' => $company->id,
            'name' => 'Referral',
        ]);

        $this->actingAs($user)->post('/leads', [
            'name' => 'Blake Homeowner',
            'phone' => '480-555-0188',
            'email' => 'blake@example.test',
            'lead_source_id' => $source->id,
            'site_address' => '789 E Test Ave',
            'city' => 'Mesa',
            'state' => 'AZ',
            'site_visit_scheduled_at' => now()->addDays(2)->setTime(14, 30)->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $lead = Lead::where('name', 'Blake Homeowner')->firstOrFail();

        $this->assertSame(LeadStatus::SiteVisit, $lead->status);
        $this->assertSame('14:30', $lead->site_visit_scheduled_at->format('H:i'));
    }

    public function test_past_site_visit_syncs_to_pending_estimate_when_leads_are_viewed(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $source = LeadSource::create([
            'company_id' => $company->id,
            'name' => 'Referral',
        ]);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'lead_source_id' => $source->id,
            'status' => LeadStatus::SiteVisit,
            'site_visit_scheduled_at' => now()->subHour(),
        ]);

        $this->actingAs($user)->get('/leads')->assertOk();

        $this->assertSame(LeadStatus::PendingEstimate, $lead->refresh()->status);
        $this->assertNotNull($lead->pending_estimate_started_at);
    }

    public function test_estimate_calculator_uses_cents_and_basis_points(): void
    {
        $estimate = $this->makeEstimateWithItem();

        $this->assertSame(10500, $estimate->items->first()->total_cents);
        $this->assertSame(3750, $estimate->direct_cost_cents);
        $this->assertSame(375, $estimate->overhead_cents);
        $this->assertSame(5893, $estimate->selling_price_cents);
        $this->assertSame(1768, $estimate->profit_cents);
        $this->assertSame(3000, $estimate->gross_margin_basis_points);
    }

    public function test_assembly_formula_evaluator_allows_only_whitelisted_inputs(): void
    {
        $evaluator = app(AssemblyFormulaEvaluator::class);

        $this->assertEqualsWithDelta(
            110.0,
            $evaluator->evaluate('quantity * (1 + waste_factor)', [
                'quantity' => 100,
                'waste_factor' => 0.1,
                'ignored' => 999,
            ]),
            0.00001,
        );

        $this->expectException(InvalidArgumentException::class);

        $evaluator->evaluate('system(quantity)', ['quantity' => 100]);
    }

    public function test_estimate_send_generates_pdf_attachment_email_token_and_activity(): void
    {
        Storage::fake('local');
        Mail::fake();

        $estimate = $this->makeEstimateWithItem(customer: null);

        $token = app(EstimateSendService::class)->send($estimate, 'client@example.test');

        $estimate->refresh();

        $this->assertSame(EstimateStatus::Emailed, $estimate->status);
        $this->assertNotNull($estimate->sent_at);
        $this->assertNotNull($estimate->public_token_hash);
        $this->assertSame(hash('sha256', $token), $estimate->public_token_hash);
        $this->assertNotNull($estimate->pdf_path);
        Storage::disk('local')->assertExists($estimate->pdf_path);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Estimate::class,
            'attachable_id' => $estimate->id,
            'type' => AttachmentType::Document->value,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $estimate->company_id,
            'event' => ActivityEvent::EstimateEmailed->value,
        ]);
        $this->assertSame(LeadStatus::EstimateSent, $estimate->lead->refresh()->status);
        Mail::assertSent(EstimateSentMail::class);
    }

    public function test_public_approve_sign_creates_customer_job_packet_deposit_and_snapshot(): void
    {
        Storage::fake('local');
        Mail::fake();

        $estimate = $this->makeEstimateWithItem(customer: null);
        $token = app(EstimateSendService::class)->send($estimate, 'jordan@example.test');

        $this->post("/estimate-review/{$token}/approve", [
            'signature_name' => 'Jordan Client',
            'signature_email' => 'jordan@example.test',
        ])->assertRedirect("/estimate-review/{$token}");

        $estimate->refresh();
        $job = Job::firstOrFail();

        $this->assertSame(EstimateStatus::Signed, $estimate->status);
        $this->assertNotNull($estimate->customer_id);
        $this->assertSame(LeadStatus::Approved, $estimate->lead->refresh()->status);
        $this->assertSame(DepositStatus::Pending, $job->deposit_status);
        $this->assertSame('Artificial Turf Install', $job->accepted_snapshot['items'][0]['name']);
        $this->assertDatabaseCount('job_packets', 1);
        $this->assertDatabaseCount('deposits', 1);
        $this->assertSame(2947, Deposit::first()->amount_cents);
    }

    public function test_public_decline_revise_bid_sets_lead_pending_estimate_without_creating_job(): void
    {
        Storage::fake('local');
        Mail::fake();

        $estimate = $this->makeEstimateWithItem(customer: null);
        $token = app(EstimateSendService::class)->send($estimate, 'jordan@example.test');

        $this->post("/estimate-review/{$token}/decline", [
            'decline_reason_type' => 'revise_bid',
            'reason' => 'Need to reduce scope first.',
        ])->assertRedirect("/estimate-review/{$token}");

        $estimate->refresh();

        $this->assertSame(EstimateStatus::Declined, $estimate->status);
        $this->assertSame(EstimateDeclineReasonType::ReviseBid, $estimate->decline_reason_type);
        $this->assertSame('Need to reduce scope first.', $estimate->declined_reason);
        $this->assertSame(LeadStatus::PendingEstimate, $estimate->lead->refresh()->status);
        $this->assertSame(0, Job::count());
    }

    public function test_public_decline_no_follow_up_sets_lead_closed_without_creating_job(): void
    {
        Storage::fake('local');
        Mail::fake();

        $estimate = $this->makeEstimateWithItem(customer: null);
        $token = app(EstimateSendService::class)->send($estimate, 'jordan@example.test');

        $this->post("/estimate-review/{$token}/decline", [
            'decline_reason_type' => 'no_follow_up',
            'reason' => 'No follow up requested.',
        ])->assertRedirect("/estimate-review/{$token}");

        $estimate->refresh();

        $this->assertSame(EstimateStatus::Declined, $estimate->status);
        $this->assertSame(EstimateDeclineReasonType::NoFollowUp, $estimate->decline_reason_type);
        $this->assertSame('No follow up requested.', $estimate->declined_reason);
        $this->assertSame(LeadStatus::Closed, $estimate->lead->refresh()->status);
        $this->assertSame(0, Job::count());
    }

    public function test_approved_and_closed_leads_are_excluded_from_active_scope(): void
    {
        Lead::factory()->create(['status' => LeadStatus::PendingContact]);
        Lead::factory()->create(['status' => LeadStatus::PendingEstimate]);
        Lead::factory()->create(['status' => LeadStatus::Approved]);
        Lead::factory()->create(['status' => LeadStatus::Closed]);

        $statuses = Lead::active()->get()->pluck('status')->all();

        $this->assertCount(2, $statuses);
        $this->assertContains(LeadStatus::PendingContact, $statuses);
        $this->assertContains(LeadStatus::PendingEstimate, $statuses);
        $this->assertNotContains(LeadStatus::Approved, $statuses);
        $this->assertNotContains(LeadStatus::Closed, $statuses);
    }

    public function test_scope_item_add_paths_for_assembly_material_and_custom_line_items(): void
    {
        $estimate = $this->makeEstimateWithItem();
        $user = User::factory()->create(['company_id' => $estimate->company_id]);
        $assembly = Assembly::factory()->create([
            'company_id' => $estimate->company_id,
            'name' => 'Paver Patio',
            'selling_price_cents' => 2000,
            'base_cost_cents' => 1200,
        ]);
        $material = Material::factory()->create([
            'company_id' => $estimate->company_id,
            'type' => MaterialType::PhysicalMaterial,
            'name' => 'Decorative Gravel',
            'unit_cost_cents' => 500,
            'selling_price_cents' => 700,
        ]);

        $this->actingAs($user)->post("/estimates/{$estimate->id}/items", [
            'item_type' => 'assembly',
            'assembly_id' => $assembly->id,
            'quantity' => 2,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post("/estimates/{$estimate->id}/items", [
            'item_type' => 'material',
            'material_id' => $material->id,
            'quantity' => 3,
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post("/estimates/{$estimate->id}/items", [
            'item_type' => 'custom',
            'name' => 'Design Allowance',
            'quantity' => 1,
            'unit' => 'each',
            'unit_price_cents' => 50000,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('estimate_items', ['estimate_id' => $estimate->id, 'name' => 'Paver Patio', 'item_type' => 'assembly']);
        $this->assertDatabaseHas('estimate_items', ['estimate_id' => $estimate->id, 'name' => 'Decorative Gravel', 'item_type' => 'material']);
        $this->assertDatabaseHas('estimate_items', ['estimate_id' => $estimate->id, 'name' => 'Design Allowance', 'item_type' => 'custom']);
    }

    public function test_settings_pages_update_company_profile_fields(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get('/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Index'));

        $this->actingAs($user)->get('/settings/company-profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/CompanyProfile'));

        $this->actingAs($user)->post('/settings/company-profile', [
            'name' => 'Normalized Landscape',
            'industry' => 'landscaping',
            'email' => 'office@example.test',
            'phone' => '480-555-0100',
            'website' => 'https://example.test',
            'address' => '100 Test Way',
            'city' => 'Mesa',
            'state' => 'AZ',
            'postal_code' => '85201',
            'contractor_license_number' => 'ROC-12345',
            'brand_primary_color' => '#0f7a3d',
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame('Normalized Landscape', $company->name);
        $this->assertSame('ROC-12345', $company->contractor_license_number);
        $this->assertSame('#0f7a3d', $company->brand_primary_color);

        $this->actingAs($user)->post('/settings/company-profile', [
            'name' => 'Normalized Landscape',
            'industry' => 'landscaping',
            'email' => 'office@example.test',
            'phone' => '480-555-0100',
            'website' => 'https://example.test',
            'address' => '100 Test Way',
            'city' => 'Mesa',
            'state' => 'AZ',
            'postal_code' => '85201',
            'contractor_license_number' => 'ROC-12345',
            'brand_primary_color' => '',
        ])->assertRedirect();

        $this->assertNull($company->refresh()->brand_primary_color);

        Storage::fake('public');

        $this->actingAs($user)->post('/settings/company-profile', [
            'name' => 'Normalized Landscape',
            'industry' => 'landscaping',
            'email' => 'office@example.test',
            'phone' => '480-555-0100',
            'website' => 'https://example.test',
            'address' => '100 Test Way',
            'city' => 'Mesa',
            'state' => 'AZ',
            'postal_code' => '85201',
            'contractor_license_number' => 'ROC-12345',
            'brand_primary_color' => '',
            'logo' => UploadedFile::fake()->image('logo.png', 160, 90),
        ])->assertRedirect();

        $company->refresh();
        $this->assertNotNull($company->logo_path);
        Storage::disk('public')->assertExists($company->logo_path);
        $this->actingAs($user)->get(route('settings.company-logo', $company))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_company_defaults_persist_templates_markups_and_lead_sources(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get('/settings/company-defaults')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/CompanyDefaults'));

        $this->actingAs($user)->put('/settings/company-defaults', [
            'default_price_sheet_markup_basis_points' => 2500,
            'estimate_template' => [
                'terms_text' => 'Custom estimate terms.',
                'email_subject' => 'Your estimate is ready',
                'email_body' => 'Please review this estimate.',
                'sections' => [
                    ['key' => 'header', 'enabled' => true],
                    ['key' => 'scope_items', 'enabled' => true],
                    ['key' => 'terms', 'enabled' => false],
                    ['key' => 'prepared_for', 'enabled' => true],
                    ['key' => 'project_site', 'enabled' => true],
                    ['key' => 'scope_summary', 'enabled' => true],
                    ['key' => 'price_summary', 'enabled' => true],
                ],
            ],
            'job_packet_template' => [
                'recipients' => ['handoff@example.test', 'handoff@example.test', 'ops@example.test'],
                'sections' => [
                    ['key' => 'header', 'enabled' => true],
                    ['key' => 'overview', 'enabled' => true],
                    ['key' => 'materials_scope', 'enabled' => false],
                    ['key' => 'commercial_summary', 'enabled' => true],
                ],
            ],
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame(2500, $company->default_price_sheet_markup_basis_points);

        $estimateTemplate = DocumentTemplate::where('company_id', $company->id)
            ->where('type', 'estimate')
            ->with('sections')
            ->firstOrFail();
        $this->assertSame('Custom estimate terms.', $estimateTemplate->terms_text);
        $this->assertSame('Your estimate is ready', $estimateTemplate->email_subject);
        $this->assertFalse($estimateTemplate->sections->firstWhere('section_key', 'terms')->enabled);
        $this->assertSame('scope_items', $estimateTemplate->sections->sortBy('sort_order')->values()[1]->section_key);

        $jobPacketTemplate = DocumentTemplate::where('company_id', $company->id)
            ->where('type', 'job_packet')
            ->with('sections', 'recipients')
            ->firstOrFail();
        $this->assertFalse($jobPacketTemplate->sections->firstWhere('section_key', 'materials_scope')->enabled);
        $this->assertSame(['handoff@example.test', 'ops@example.test'], $jobPacketTemplate->recipients->pluck('email')->all());

        $this->actingAs($user)->post('/settings/company-defaults/lead-sources', [
            'name' => 'Yard Sign',
            'channel' => 'offline',
            'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('lead_sources', [
            'company_id' => $company->id,
            'name' => 'Yard Sign',
            'channel' => 'offline',
            'is_active' => true,
        ]);
    }

    public function test_legacy_settings_json_is_backfilled_into_normalized_settings(): void
    {
        $company = Company::factory()->create([
            'settings' => [
                'company_profile' => ['brand_color' => '#123456'],
                'estimate_terms' => 'Legacy estimate terms.',
                'company_defaults' => [
                    'late_estimate_procedure' => [
                        'days_allowed' => 4,
                        'contact_emails' => ['legacy@example.test'],
                    ],
                ],
            ],
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get('/settings/company-defaults')->assertOk();
        $this->actingAs($user)->get('/settings/notifications')->assertOk();

        $estimateTemplate = DocumentTemplate::where('company_id', $company->id)
            ->where('type', 'estimate')
            ->firstOrFail();
        $rule = LeadStatusReminderRule::where('company_id', $company->id)
            ->where('lead_status', LeadStatus::PendingEstimate)
            ->with('recipients')
            ->firstOrFail();

        $this->assertSame('Legacy estimate terms.', $estimateTemplate->terms_text);
        $this->assertTrue($rule->is_enabled);
        $this->assertSame(4, $rule->days_after_status);
        $this->assertSame(['legacy@example.test'], $rule->recipients->pluck('email')->all());
    }

    public function test_notifications_persist_normalized_lead_status_rules_with_validation(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get('/settings/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Notifications'));

        $rules = collect(LeadStatus::cases())->map(fn (LeadStatus $status): array => [
            'lead_status' => $status->value,
            'is_enabled' => $status === LeadStatus::PendingEstimate,
            'days_after_status' => $status === LeadStatus::PendingEstimate ? 3 : null,
            'recipients' => $status === LeadStatus::PendingEstimate
                ? ['sales@example.test', 'sales@example.test', 'ops@example.test']
                : [],
        ])->values()->all();

        $this->actingAs($user)->put('/settings/notifications', [
            'digest' => [
                'digest_frequency' => 'weekly',
                'include_pipeline_summary' => true,
                'include_late_estimates' => true,
                'include_recent_activity' => false,
                'include_sales_summary' => true,
            ],
            'rules' => $rules,
        ])->assertRedirect();

        $rule = LeadStatusReminderRule::where('company_id', $company->id)
            ->where('lead_status', LeadStatus::PendingEstimate)
            ->with('recipients')
            ->firstOrFail();
        $this->assertTrue($rule->is_enabled);
        $this->assertSame(3, $rule->days_after_status);
        $this->assertSame(['sales@example.test', 'ops@example.test'], $rule->recipients->pluck('email')->all());

        $invalidRules = collect(LeadStatus::cases())->map(fn (LeadStatus $status): array => [
            'lead_status' => $status->value,
            'is_enabled' => $status === LeadStatus::PendingEstimate,
            'days_after_status' => $status === LeadStatus::PendingEstimate ? 3 : null,
            'recipients' => [],
        ])->values()->all();

        $this->actingAs($user)->from('/settings/notifications')->put('/settings/notifications', [
            'digest' => [
                'digest_frequency' => 'weekly',
                'include_pipeline_summary' => true,
                'include_late_estimates' => true,
                'include_recent_activity' => false,
                'include_sales_summary' => true,
            ],
            'rules' => $invalidRules,
        ])->assertRedirect('/settings/notifications')
            ->assertSessionHasErrors('rules.2.recipients');
    }

    public function test_settings_price_sheet_materials_use_true_cost_plus_markup(): void
    {
        $company = Company::factory()->create(['default_price_sheet_markup_basis_points' => 2500]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get('/settings/price-sheet')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/PriceSheet'));

        $this->actingAs($user)->post('/settings/price-sheet/materials', [
            'name' => 'Premium Gravel',
            'type' => MaterialType::PhysicalMaterial->value,
            'sku' => 'GRAVEL-001',
            'category' => 'Gravel',
            'unit' => 'ton',
            'unit_cost_cents' => 10000,
            'markup_basis_points' => 2500,
            'vendor' => 'Supplier',
            'description' => 'Decorative rock.',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('materials', [
            'company_id' => $company->id,
            'name' => 'Premium Gravel',
            'unit_cost_cents' => 10000,
            'markup_basis_points' => 2500,
            'selling_price_cents' => 12500,
        ]);
    }

    public function test_pending_estimate_tracking_is_set_and_cleared_with_status_transitions(): void
    {
        $lead = Lead::factory()->create([
            'status' => LeadStatus::PendingContact,
            'pending_estimate_started_at' => null,
            'late_estimate_last_notified_at' => null,
        ]);

        $workflow = app(LeadStatusWorkflow::class);

        $workflow->markPendingEstimate($lead);
        $lead->refresh();

        $this->assertSame(LeadStatus::PendingEstimate, $lead->status);
        $this->assertNotNull($lead->pending_estimate_started_at);

        $lead->forceFill(['late_estimate_last_notified_at' => now()])->save();
        $workflow->markEstimateSent($lead->refresh());
        $lead->refresh();

        $this->assertSame(LeadStatus::EstimateSent, $lead->status);
        $this->assertNull($lead->pending_estimate_started_at);
        $this->assertNull($lead->late_estimate_last_notified_at);

        $workflow->markPendingEstimate($lead);
        $lead->refresh();

        $this->assertSame(LeadStatus::PendingEstimate, $lead->status);
        $this->assertNotNull($lead->pending_estimate_started_at);
        $this->assertNull($lead->late_estimate_last_notified_at);
    }

    public function test_late_estimate_command_queues_daily_notifications_and_suppresses_same_day(): void
    {
        Mail::fake();

        $company = Company::factory()->create([
            'settings' => [
                'company_defaults' => [
                    'late_estimate_procedure' => [
                        'days_allowed' => 2,
                        'contact_emails' => ['sales@example.test'],
                    ],
                ],
            ],
        ]);
        Lead::factory()->create([
            'company_id' => $company->id,
            'status' => LeadStatus::PendingEstimate,
            'pending_estimate_started_at' => now()->subDays(3),
            'phone' => '480-555-0199',
            'requested_project_specifications' => 'Needs revised paver and turf estimate.',
        ]);

        $disabledCompany = Company::factory()->create([
            'settings' => [
                'company_defaults' => [
                    'late_estimate_procedure' => [
                        'days_allowed' => 2,
                        'contact_emails' => [],
                    ],
                ],
            ],
        ]);
        Lead::factory()->create([
            'company_id' => $disabledCompany->id,
            'status' => LeadStatus::PendingEstimate,
            'pending_estimate_started_at' => now()->subDays(10),
        ]);

        $this->artisan('bidscape:late-estimates')
            ->expectsOutput('Late estimate notifications queued: 1')
            ->assertExitCode(0);

        Mail::assertQueued(LateEstimateProcedureMail::class, 1);
        Mail::assertQueued(
            LateEstimateProcedureMail::class,
            fn (LateEstimateProcedureMail $mail): bool => $mail->company->is($company)
                && $mail->hasTo('sales@example.test')
                && $mail->daysPendingEstimate >= 3,
        );
        $this->assertNotNull(Lead::where('company_id', $company->id)->firstOrFail()->late_estimate_last_notified_at);

        $this->artisan('bidscape:late-estimates')
            ->expectsOutput('Late estimate notifications queued: 0')
            ->assertExitCode(0);
        Mail::assertQueued(LateEstimateProcedureMail::class, 1);

        $this->travel(1)->day();

        $this->artisan('bidscape:late-estimates')
            ->expectsOutput('Late estimate notifications queued: 1')
            ->assertExitCode(0);
        Mail::assertQueued(LateEstimateProcedureMail::class, 2);

        $this->travelBack();
    }

    public function test_late_estimate_notifications_are_company_scoped(): void
    {
        Mail::fake();

        $company = Company::factory()->create([
            'settings' => [
                'company_defaults' => [
                    'late_estimate_procedure' => [
                        'days_allowed' => 1,
                        'contact_emails' => ['first@example.test'],
                    ],
                ],
            ],
        ]);
        $otherCompany = Company::factory()->create([
            'settings' => [
                'company_defaults' => [
                    'late_estimate_procedure' => [
                        'days_allowed' => 1,
                        'contact_emails' => ['second@example.test'],
                    ],
                ],
            ],
        ]);
        $lead = Lead::factory()->create([
            'company_id' => $company->id,
            'status' => LeadStatus::PendingEstimate,
            'pending_estimate_started_at' => now()->subDays(2),
        ]);
        Lead::factory()->create([
            'company_id' => $otherCompany->id,
            'status' => LeadStatus::PendingEstimate,
            'pending_estimate_started_at' => now()->subDays(2),
        ]);

        $sent = app(LateEstimateProcedureService::class)->runForCompany($company);

        $this->assertSame(1, $sent);
        Mail::assertQueued(LateEstimateProcedureMail::class, 1);
        Mail::assertQueued(
            LateEstimateProcedureMail::class,
            fn (LateEstimateProcedureMail $mail): bool => $mail->lead->is($lead)
                && $mail->company->is($company)
                && $mail->hasTo('first@example.test')
                && ! $mail->hasTo('second@example.test'),
        );
    }

    public function test_company_scoping_blocks_cross_company_endpoint_access(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $estimate = $this->makeEstimateWithItem($otherCompany);
        $assembly = Assembly::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($user)->post("/estimates/{$estimate->id}/items", [
            'item_type' => 'assembly',
            'assembly_id' => $assembly->id,
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_report_builders_are_company_scoped(): void
    {
        $company = Company::factory()->create(['name' => 'Scoped Company']);
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);

        $this->makeSoldJob($company, 10000);
        $this->makeSoldJob($otherCompany, 99999);

        $summary = app(ReportService::class)->salesSummary($company);

        $this->assertSame(10000, $summary['total_contract_value_cents']);
        $this->assertSame(1, $summary['contracts_won']);
    }

    public function test_job_packet_pdf_generation_uses_storage_abstraction(): void
    {
        Storage::fake('local');
        Mail::fake();

        $estimate = $this->makeEstimateWithItem(customer: null);
        $token = app(EstimateSendService::class)->send($estimate, 'jordan@example.test');
        $this->post("/estimate-review/{$token}/approve", [
            'signature_name' => 'Jordan Client',
            'signature_email' => 'jordan@example.test',
        ]);
        $packet = Job::firstOrFail()->packet;

        $path = app(JobPacketPdfService::class)->generate($packet);

        $this->assertSame('job-packets/'.$packet->packet_number.'.pdf', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame($path, $packet->refresh()->pdf_path);
        $this->assertNotNull($packet->generated_at);
    }

    private function makeEstimateWithItem(
        ?Company $company = null,
        ?Customer $customer = null,
        ?Lead $lead = null,
        ?PaymentTerm $paymentTerm = null,
    ): Estimate {
        $company ??= Company::factory()->create();
        $lead ??= Lead::factory()->create([
            'company_id' => $company->id,
            'customer_id' => null,
            'name' => 'Jordan Client',
            'email' => 'jordan@example.test',
            'status' => LeadStatus::PendingEstimate,
        ]);
        $paymentTerm ??= $this->makePaymentTerm($company);

        $estimate = Estimate::create([
            'company_id' => $company->id,
            'customer_id' => $customer?->id,
            'lead_id' => $lead->id,
            'payment_term_id' => $paymentTerm->id,
            'estimate_number' => 'EST-'.str_pad((string) (Estimate::count() + 1), 3, '0', STR_PAD_LEFT),
            'project_name' => 'Backyard Renovation',
            'status' => EstimateStatus::InReview,
            'overhead_basis_points' => 1000,
            'target_margin_basis_points' => 3000,
            'scope_summary' => 'Install approved landscape scope.',
        ]);

        $assembly = Assembly::factory()->create([
            'company_id' => $company->id,
            'name' => 'Artificial Turf Install',
            'category' => 'Ground Cover',
            'unit' => 'sqft',
            'base_cost_cents' => 500,
            'selling_price_cents' => 1000,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'assembly_id' => $assembly->id,
            'item_type' => 'assembly',
            'name' => 'Artificial Turf Install',
            'subtitle' => 'Premium 80 oz',
            'quantity' => '10.500',
            'unit' => 'sqft',
            'unit_price_cents' => 1000,
            'material_cost_cents' => 2000,
            'labor_cost_cents' => 1000,
            'equipment_cost_cents' => 500,
            'delivery_cost_cents' => 250,
            'sort_order' => 1,
        ]);

        return app(EstimateCalculator::class)->recalculate($estimate);
    }

    private function makePaymentTerm(Company $company, int $depositBasisPoints = 5000): PaymentTerm
    {
        return PaymentTerm::create([
            'company_id' => $company->id,
            'name' => 'Deposit Terms',
            'deposit_basis_points' => $depositBasisPoints,
            'terms_text' => 'Deposit due on signing.',
            'is_default' => true,
        ]);
    }

    private function makeSoldJob(Company $company, int $contractValueCents): Job
    {
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $estimate = Estimate::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'estimate_number' => 'EST-JOB-'.str_pad((string) (Estimate::count() + 1), 3, '0', STR_PAD_LEFT),
            'project_name' => 'Scoped Sold Job',
            'status' => EstimateStatus::Signed,
            'selling_price_cents' => $contractValueCents,
            'gross_margin_basis_points' => 3000,
        ]);
        $contract = Contract::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'estimate_id' => $estimate->id,
            'contract_number' => 'CON-JOB-'.str_pad((string) (Contract::count() + 1), 3, '0', STR_PAD_LEFT),
            'status' => ContractStatus::Signed,
            'total_cents' => $contractValueCents,
            'signed_at' => now(),
        ]);

        return Job::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'estimate_id' => $estimate->id,
            'contract_id' => $contract->id,
            'job_number' => 'JOB-'.str_pad((string) (Job::count() + 1), 3, '0', STR_PAD_LEFT),
            'project_name' => 'Scoped Sold Job',
            'status' => JobStatus::Sold,
            'contract_value_cents' => $contractValueCents,
            'deposit_status' => DepositStatus::Pending,
            'contract_signed_at' => now(),
        ]);
    }
}
