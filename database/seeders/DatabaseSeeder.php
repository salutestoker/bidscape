<?php

namespace Database\Seeders;

use App\Enums\ActivityEvent;
use App\Enums\AttachmentType;
use App\Enums\DepositStatus;
use App\Enums\EstimateDeclineReasonType;
use App\Enums\EstimateStatus;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\MaterialType;
use App\Models\ActivityLog;
use App\Models\Assembly;
use App\Models\AssemblyComponent;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Material;
use App\Models\PaymentTerm;
use App\Models\User;
use App\Services\EstimateAcceptanceService;
use App\Services\EstimateCalculator;
use App\Services\EstimatePdfService;
use App\Services\MoneyCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            User::where('email', 'nick@desertridge.test')->delete();
            Company::where('name', 'Desert Ridge Landscaping')->first()?->delete();

            $company = Company::create([
                'name' => 'Desert Ridge Landscaping',
                'industry' => 'landscaping',
                'email' => 'hello@desertridge.test',
                'phone' => '480-555-0124',
                'website' => 'https://desertridge.test',
                'address' => '1234 E Desert Ave',
                'city' => 'Mesa',
                'state' => 'AZ',
                'postal_code' => '85204',
                'default_overhead_basis_points' => 1000,
                'default_target_margin_basis_points' => 3000,
                'brand_primary_color' => '#087a3d',
                'default_price_sheet_markup_basis_points' => 3000,
                'settings' => [
                    'weather_location' => 'Mesa, Arizona',
                    'company_profile' => ['brand_color' => '#087a3d'],
                    'company_defaults' => [
                        'late_estimate_procedure' => [
                            'days_allowed' => 3,
                            'contact_emails' => ['nick@desertridge.test'],
                        ],
                    ],
                    'estimate_terms' => 'Estimate valid for 30 days. Deposit is due after signature.',
                    'lead_source_defaults' => ['default_owner' => 'Nick Martinez'],
                ],
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => 'Nick Martinez',
                'email' => 'nick@desertridge.test',
                'password' => Hash::make('password'),
                'title' => 'Owner / Estimator',
                'email_verified_at' => now(),
            ]);

            $paymentTerm = PaymentTerm::create([
                'company_id' => $company->id,
                'name' => '50% Deposit / 50% Completion',
                'deposit_basis_points' => 5000,
                'terms_text' => '50% deposit due after signature. Remaining balance due after final sales handoff.',
                'is_default' => true,
            ]);

            $sources = collect(['Website', 'Referral', 'Google Ads', 'Instagram', 'Home Show'])
                ->mapWithKeys(fn (string $name) => [
                    $name => LeadSource::create([
                        'company_id' => $company->id,
                        'name' => $name,
                        'channel' => $name === 'Referral' ? 'word_of_mouth' : strtolower(str_replace(' ', '_', $name)),
                    ]),
                ]);

            $materials = $this->seedMaterials($company);
            $assemblies = $this->seedAssemblies($company, $materials);
            $customers = $this->seedCustomers($company, $sources);
            $leads = $this->seedLeads($company, $sources, $customers, $user);
            $this->seedEstimates($company, $paymentTerm, $assemblies, $materials, $customers, $leads);
            $this->seedRecentActivity($company, $user);
        });
    }

    /**
     * @return array<string, Material>
     */
    private function seedMaterials(Company $company): array
    {
        $rows = [
            ['Premium Turf 80 oz', MaterialType::PhysicalMaterial, 'TURF-080-PREMIUM', 'Turf', 'sqft', 315, 4000, 'Pioneer', '/images/demo/turf.svg'],
            ['Class II Road Base', MaterialType::PhysicalMaterial, 'BASE-CLASS-II', 'Base', 'ton', 1400, 3500, 'Southwest Rock', '/images/demo/gravel.svg'],
            ['Infill (PP)', MaterialType::PhysicalMaterial, 'TURF-INFILL-PP', 'Turf', 'lb', 68, 3000, 'Pioneer', '/images/demo/gravel.svg'],
            ['Seam Tape', MaterialType::PhysicalMaterial, 'TURF-SEAM-TAPE', 'Turf', 'roll', 1875, 5000, 'Pioneer', '/images/demo/turf.svg'],
            ['Drip Line 17mm', MaterialType::PhysicalMaterial, 'IRR-DRIP-17', 'Irrigation', 'ft', 45, 3000, 'RainPro', '/images/demo/irrigation.svg'],
            ['Paver Pad', MaterialType::PhysicalMaterial, 'PAVER-PAD', 'Pavers', 'sqft', 50, 3500, 'Belgard', '/images/demo/pavers.svg'],
            ['LED Path Light', MaterialType::PhysicalMaterial, 'LIGHT-PATH-LED', 'Lighting', 'each', 6200, 4000, 'Volt', '/images/demo/lighting.svg'],
            ['Decorative Gravel', MaterialType::PhysicalMaterial, 'GRAVEL-MADISON-GOLD', 'Gravel', 'ton', 2675, 3000, 'Southwest Rock', '/images/demo/gravel.svg'],
            ['Estimator Labor', MaterialType::Labor, 'LABOR-INSTALL', 'Labor', 'hour', 6500, 3000, 'Internal', null],
            ['Compact Loader', MaterialType::Equipment, 'EQUIP-LOADER', 'Equipment', 'day', 28000, 2500, 'Internal', null],
            ['Material Delivery', MaterialType::Delivery, 'DELIVERY-LOCAL', 'Delivery', 'trip', 17500, 2500, 'Internal', null],
            ['Design Allowance', MaterialType::Allowance, 'ALLOW-DESIGN', 'Allowance', 'each', 50000, 2000, 'Internal', null],
        ];

        $materials = [];

        foreach ($rows as [$name, $type, $sku, $category, $unit, $cost, $markup, $vendor, $photo]) {
            $materials[$name] = Material::create([
                'company_id' => $company->id,
                'name' => $name,
                'type' => $type,
                'sku' => $sku,
                'category' => $category,
                'unit' => $unit,
                'unit_cost_cents' => $cost,
                'markup_basis_points' => $markup,
                'selling_price_cents' => MoneyCalculator::priceForMarkup($cost, $markup),
                'hourly_rate_cents' => $type === MaterialType::Labor ? $cost : null,
                'pricing_method' => $type === MaterialType::Labor ? 'hourly' : 'unit',
                'vendor' => $vendor,
                'photo_path' => $photo,
                'description' => "{$name} component available for estimate scope items.",
                'lead_time_days' => $type === MaterialType::PhysicalMaterial ? 3 : null,
            ]);
        }

        return $materials;
    }

    /**
     * @param  array<string, Material>  $materials
     * @return array<string, Assembly>
     */
    private function seedAssemblies(Company $company, array $materials): array
    {
        $rows = [
            ['Artificial Turf Install', 'Ground Cover', 'sqft', '/images/demo/turf.svg', 728, 1005, 3500, '1.8', ['Premium Turf 80 oz', 'Class II Road Base', 'Infill (PP)', 'Seam Tape']],
            ['Paver Patio', 'Hardscape', 'sqft', '/images/demo/pavers.svg', 1425, 1875, 3000, '2.4', ['Paver Pad', 'Class II Road Base']],
            ['Decorative Gravel', 'Ground Cover', 'sqft', '/images/demo/gravel.svg', 340, 385, 3500, '0.7', ['Decorative Gravel']],
            ['Drip Irrigation', 'Irrigation', 'zone', '/images/demo/irrigation.svg', 22500, 42500, 3000, '3.0', ['Drip Line 17mm']],
            ['Plant Package', 'Plants', 'qty', '/images/demo/plants.svg', 4500, 6850, 4000, '0.4', []],
            ['Low Voltage Lighting', 'Lighting', 'fixture', '/images/demo/lighting.svg', 14500, 14500, 3000, '0.6', ['LED Path Light']],
            ['Concrete Walkway', 'Hardscape', 'sqft', '/images/demo/pavers.svg', 988, 1383, 3500, '1.9', ['Class II Road Base']],
            ['Retaining Wall', 'Walls', 'sqft', '/images/demo/pavers.svg', 2860, 3718, 3000, '2.8', ['Class II Road Base']],
        ];

        $assemblies = [];

        foreach ($rows as [$name, $category, $unit, $image, $baseCost, $price, $markup, $hours, $componentNames]) {
            $assembly = Assembly::create([
                'company_id' => $company->id,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'description' => "Reusable {$category} estimating assembly for {$name}.",
                'image_path' => $image,
                'markup_basis_points' => $markup,
                'overhead_basis_points' => 1000,
                'target_margin_basis_points' => 3000,
                'waste_factor_basis_points' => $name === 'Artificial Turf Install' ? 1000 : 500,
                'base_depth_inches' => in_array($category, ['Ground Cover', 'Hardscape', 'Walls'], true) ? 3 : null,
                'labor_hours_per_unit' => $hours,
                'default_minutes_per_unit' => bcmul($hours, '60', 2),
                'production_rate_per_day' => $unit === 'sqft' ? 540 : null,
                'base_cost_cents' => $baseCost,
                'selling_price_cents' => $price,
                'formula_metadata' => ['quantity_formula' => 'quantity * (1 + waste_factor)'],
            ]);

            foreach ($componentNames as $index => $componentName) {
                $material = $materials[$componentName];
                AssemblyComponent::create([
                    'assembly_id' => $assembly->id,
                    'material_id' => $material->id,
                    'component_type' => $material->type->value,
                    'name' => $material->name,
                    'unit' => $material->unit,
                    'quantity_per_unit' => $componentName === 'Class II Road Base' ? '0.009000' : '1.000000',
                    'quantity_formula' => $componentName === 'Class II Road Base' ? 'quantity * base_depth * 0.003' : 'quantity * (1 + waste_factor)',
                    'unit_cost_cents' => $material->unit_cost_cents,
                    'sort_order' => $index + 1,
                ]);
            }

            $assemblies[$name] = $assembly;
        }

        return $assemblies;
    }

    /**
     * @param  Collection<string, LeadSource>  $sources
     * @return Collection<int, Customer>
     */
    private function seedCustomers(Company $company, Collection $sources): Collection
    {
        $names = ['John Smith', 'Maria Garcia', 'David Johnson', 'Sarah Rodriguez', 'Michael Brown', 'Lisa Lopez', 'Robert Wilson', 'Jennifer Davis', 'Carlos Martinez', 'Emily Clark'];

        return collect($names)->map(function (string $name, int $index) use ($company, $sources): Customer {
            return Customer::create([
                'company_id' => $company->id,
                'lead_source_id' => $sources->values()[$index % $sources->count()]->id,
                'name' => $name,
                'email' => strtolower(str_replace(' ', '', $name)).'@email.com',
                'phone' => '480-555-0'.str_pad((string) (140 + ($index * 7)), 3, '0', STR_PAD_LEFT),
                'site_address' => (1234 + $index).' E Desert Ave',
                'city' => 'Mesa',
                'state' => 'AZ',
                'postal_code' => '8520'.$index,
                'notes' => 'Prefers concise estimate details and email follow-up.',
                'last_activity_at' => now()->subDays($index + 1),
            ]);
        });
    }

    /**
     * @param  Collection<string, LeadSource>  $sources
     * @param  Collection<int, Customer>  $customers
     * @return Collection<int, Lead>
     */
    private function seedLeads(Company $company, Collection $sources, Collection $customers, User $user): Collection
    {
        $statuses = [
            LeadStatus::PendingContact,
            LeadStatus::SiteVisit,
            LeadStatus::EstimateSent,
            LeadStatus::PendingEstimate,
            LeadStatus::PendingContact,
            LeadStatus::PendingEstimate,
            LeadStatus::PendingContact,
            LeadStatus::SiteVisit,
            LeadStatus::PendingEstimate,
            LeadStatus::SiteVisit,
            LeadStatus::Approved,
            LeadStatus::Closed,
            LeadStatus::EstimateSent,
            LeadStatus::PendingEstimate,
            LeadStatus::PendingContact,
            LeadStatus::SiteVisit,
            LeadStatus::PendingContact,
            LeadStatus::PendingEstimate,
            LeadStatus::SiteVisit,
            LeadStatus::Closed,
        ];

        return collect($statuses)->map(function (LeadStatus $status, int $index) use ($company, $sources, $customers, $user): Lead {
            $customer = $customers[$index % $customers->count()];

            return Lead::create([
                'company_id' => $company->id,
                'lead_source_id' => $sources->values()[$index % $sources->count()]->id,
                'customer_id' => $status === LeadStatus::Approved ? $customer->id : null,
                'assigned_user_id' => $user->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'site_address' => $customer->site_address,
                'city' => $customer->city,
                'state' => $customer->state,
                'postal_code' => $customer->postal_code,
                'status' => $status,
                'contact_preference' => $index % 2 === 0 ? 'Phone' : 'Email',
                'project_interest' => ['Backyard renovation', 'Front yard refresh', 'Pool area hardscape', 'Artificial turf conversion'][$index % 4],
                'requested_project_specifications' => 'Customer requested a clear scope, itemized pricing, and signature-ready estimate PDF.',
                'site_notes' => 'Gate is narrow. Bring smaller material carts if access is confirmed.',
                'internal_notes' => 'Keep sales handoff notes focused on sold scope and customer commitments.',
                'gate_code' => $index % 3 === 0 ? '2468' : null,
                'site_visit_scheduled_at' => match ($status) {
                    LeadStatus::SiteVisit => now()->addDays(2 + $index)->setTime(10, 0),
                    LeadStatus::PendingEstimate => now()->subDays(1 + ($index % 4))->setTime(10, 0),
                    default => null,
                },
                'pending_estimate_started_at' => $status === LeadStatus::PendingEstimate ? now()->subDays(1 + ($index % 4))->setTime(10, 0) : null,
                'next_follow_up_at' => now()->addDays(($index % 5) + 1)->setTime(9 + ($index % 5), 0),
                'lost_reason' => $status === LeadStatus::Closed ? 'No follow-up after declined estimate' : null,
                'won_at' => $status === LeadStatus::Approved ? now()->subDays(10) : null,
                'lost_at' => $status === LeadStatus::Closed ? now()->subDays(8) : null,
                'created_at' => now()->subDays(20 - min($index, 19)),
                'updated_at' => now()->subDays(8 - min($index % 8, 7)),
            ]);
        });
    }

    /**
     * @param  array<string, Assembly>  $assemblies
     * @param  array<string, Material>  $materials
     * @param  Collection<int, Customer>  $customers
     * @param  Collection<int, Lead>  $leads
     */
    private function seedEstimates(Company $company, PaymentTerm $paymentTerm, array $assemblies, array $materials, Collection $customers, Collection $leads): void
    {
        $calculator = app(EstimateCalculator::class);
        $acceptance = app(EstimateAcceptanceService::class);
        $pdfs = app(EstimatePdfService::class);

        $projects = [
            ['Smith Backyard Renovation', 'signed', 0, ['Artificial Turf Install' => 1250, 'Paver Patio' => 480, 'Decorative Gravel' => 650, 'Drip Irrigation' => 3, 'Plant Package' => 22, 'Low Voltage Lighting' => 8]],
            ['Garcia Front Yard', 'signed', 1, ['Paver Patio' => 520, 'Plant Package' => 34, 'Low Voltage Lighting' => 6]],
            ['Johnson Courtyard', 'signed', 2, ['Decorative Gravel' => 880, 'Low Voltage Lighting' => 10, 'Plant Package' => 18]],
            ['Rodriguez Turf Conversion', 'signed', 3, ['Artificial Turf Install' => 820, 'Drip Irrigation' => 2]],
            ['Brown Patio Refresh', 'signed', 4, ['Paver Patio' => 410, 'Low Voltage Lighting' => 5]],
            ['Wilson Retaining Wall', 'signed', 6, ['Retaining Wall' => 360, 'Decorative Gravel' => 220]],
            ['Davis Entry Refresh', 'emailed', 7, ['Concrete Walkway' => 280, 'Plant Package' => 16]],
            ['Martinez Turf Plan', 'emailed', 8, ['Artificial Turf Install' => 950, 'Drip Irrigation' => 2]],
            ['Clark Garden Beds', 'declined', 9, ['Plant Package' => 38, 'Decorative Gravel' => 310]],
            ['Lopez Lighting Plan', 'in_review', 5, ['Low Voltage Lighting' => 14]],
            ['Smith Design Allowance', 'draft', 10, ['Plant Package' => 12]],
            ['Garcia Pool Edge', 'expired', 11, ['Decorative Gravel' => 520, 'Paver Patio' => 180]],
            ['Parker Patio Reset', 'declined_no_follow', 12, ['Paver Patio' => 220, 'Low Voltage Lighting' => 4]],
        ];

        foreach ($projects as $index => [$project, $scenario, $leadIndex, $items]) {
            $lead = $leads[$leadIndex] ?? $leads[$index];
            $customer = in_array($scenario, ['signed'], true) ? null : $customers[$leadIndex % $customers->count()];
            $estimate = Estimate::create([
                'company_id' => $company->id,
                'customer_id' => $customer?->id,
                'lead_id' => $lead->id,
                'payment_term_id' => $paymentTerm->id,
                'estimate_number' => 'EST-2026-'.str_pad((string) ($index + 101), 3, '0', STR_PAD_LEFT),
                'project_name' => $project,
                'status' => EstimateStatus::Draft,
                'builder_step' => 'scope',
                'overhead_basis_points' => 1000,
                'target_margin_basis_points' => 3000,
                'scope_summary' => 'Prepare site, install selected landscape scope, clean up, and freeze the accepted estimate into the sales handoff packet.',
                'exclusions' => 'Permits, utility relocation, and concealed condition repairs are excluded unless added in writing.',
                'terms' => 'Estimate valid for 30 days. Deposit is due after signature.',
                'expires_at' => now()->addDays(30),
                'created_at' => now()->subMonths($index % 6)->subDays($index),
                'updated_at' => now()->subDays($index),
            ]);

            $this->addEstimateItems($estimate, $assemblies, $materials, $items);
            $calculator->recalculate($estimate->load('items'));
            $estimate->refresh();

            if ($scenario === 'signed') {
                $token = 'demo-token-'.$estimate->estimate_number;
                $estimate->forceFill([
                    'status' => EstimateStatus::Emailed,
                    'sent_at' => now()->subDays(15 - min($index, 8)),
                    'public_token_hash' => hash('sha256', $token),
                    'public_token_expires_at' => now()->addDays(30),
                ])->save();
                $this->attachEstimatePdf($estimate, $pdfs, $token);
                $contract = $acceptance->approve($estimate->refresh(), $lead->name, $lead->email, '127.0.0.1');
                $job = $contract->job;

                if ($job instanceof Job && $index % 3 === 0) {
                    $job->forceFill(['status' => JobStatus::PacketReady, 'packet_ready_at' => now()->subDays(1), 'next_action' => 'Hand Off to Operations'])->save();
                    $job->packet?->forceFill(['status' => 'ready', 'ready_at' => now()->subDays(1), 'missing_requirements' => []])->save();
                }

                if ($job instanceof Job && $index % 2 === 0) {
                    $deposit = Deposit::where('project_job_id', $job->id)->first();
                    $deposit?->forceFill([
                        'status' => DepositStatus::Paid,
                        'paid_at' => now()->subDays(2),
                        'payment_method' => 'Check',
                        'reference' => 'DEP-'.$job->job_number,
                    ])->save();
                    $job->forceFill(['deposit_status' => DepositStatus::Paid])->save();
                }

                continue;
            }

            if ($scenario === 'emailed') {
                $token = 'demo-token-'.$estimate->estimate_number;
                $estimate->forceFill([
                    'status' => EstimateStatus::Emailed,
                    'sent_at' => now()->subDays(3),
                    'public_token_hash' => hash('sha256', $token),
                    'public_token_expires_at' => now()->addDays(27),
                ])->save();
                $lead->forceFill([
                    'status' => LeadStatus::EstimateSent,
                    'pending_estimate_started_at' => null,
                    'late_estimate_last_notified_at' => null,
                ])->save();
                $this->attachEstimatePdf($estimate->refresh(), $pdfs, $token);
            }

            if ($scenario === 'declined') {
                $estimate->forceFill([
                    'status' => EstimateStatus::Declined,
                    'sent_at' => now()->subDays(5),
                    'declined_at' => now()->subDays(2),
                    'decline_reason_type' => EstimateDeclineReasonType::ReviseBid,
                    'declined_reason' => 'Customer requested a lower-scope revision before moving forward.',
                ])->save();
                $lead->forceFill([
                    'status' => LeadStatus::PendingEstimate,
                    'pending_estimate_started_at' => now()->subDays(2),
                    'late_estimate_last_notified_at' => null,
                ])->save();
            }

            if ($scenario === 'declined_no_follow') {
                $estimate->forceFill([
                    'status' => EstimateStatus::Declined,
                    'sent_at' => now()->subDays(6),
                    'declined_at' => now()->subDays(3),
                    'decline_reason_type' => EstimateDeclineReasonType::NoFollowUp,
                    'declined_reason' => 'Customer declined and requested no follow-up.',
                ])->save();
                $lead->forceFill(['status' => LeadStatus::Closed, 'lost_reason' => 'Decline - No Follow Up', 'lost_at' => now()->subDays(3)])->save();
            }

            if ($scenario === 'in_review') {
                $estimate->forceFill(['status' => EstimateStatus::InReview])->save();
            }

            if ($scenario === 'expired') {
                $estimate->forceFill([
                    'status' => EstimateStatus::Expired,
                    'sent_at' => now()->subDays(45),
                    'expires_at' => now()->subDays(15),
                    'public_token_hash' => hash('sha256', 'expired-'.$estimate->estimate_number),
                    'public_token_expires_at' => now()->subDays(15),
                ])->save();
            }
        }
    }

    /**
     * @param  array<string, Assembly>  $assemblies
     * @param  array<string, Material>  $materials
     * @param  array<string, int>  $items
     */
    private function addEstimateItems(Estimate $estimate, array $assemblies, array $materials, array $items): void
    {
        $sort = 1;

        foreach ($items as $assemblyName => $quantity) {
            $assembly = $assemblies[$assemblyName];
            $materialCost = MoneyCalculator::multiplyDecimalByCents((string) $quantity, $assembly->base_cost_cents);
            $laborCost = MoneyCalculator::multiplyDecimalByCents((string) $quantity, $assembly->unit === 'sqft' ? 190 : 4500);
            $equipmentCost = in_array($assembly->category, ['Hardscape', 'Walls'], true)
                ? MoneyCalculator::multiplyDecimalByCents((string) $quantity, 65)
                : MoneyCalculator::multiplyDecimalByCents((string) $quantity, 15);

            EstimateItem::create([
                'estimate_id' => $estimate->id,
                'assembly_id' => $assembly->id,
                'item_type' => 'assembly',
                'name' => $assembly->name,
                'subtitle' => $assembly->category,
                'description' => $assembly->description,
                'quantity' => $quantity,
                'unit' => $assembly->unit,
                'unit_price_cents' => $assembly->selling_price_cents,
                'material_cost_cents' => $materialCost,
                'labor_cost_cents' => $laborCost,
                'equipment_cost_cents' => $equipmentCost,
                'delivery_cost_cents' => $sort === 1 ? 55000 : 0,
                'markup_basis_points' => $assembly->markup_basis_points,
                'sort_order' => $sort++,
                'thumbnail_path' => $assembly->image_path,
                'notes' => 'Accepted quantity and source snapshot are retained for the job packet.',
                'source_snapshot' => $assembly->only(['id', 'name', 'category', 'unit', 'base_cost_cents', 'selling_price_cents']),
            ]);
        }

        $labor = $materials['Estimator Labor'];
        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'material_id' => $labor->id,
            'item_type' => 'labor',
            'name' => 'Sales Scope Coordination',
            'subtitle' => 'Labor',
            'quantity' => 2,
            'unit' => 'hour',
            'unit_price_cents' => $labor->selling_price_cents,
            'labor_cost_cents' => MoneyCalculator::multiplyDecimalByCents('2', $labor->unit_cost_cents),
            'sort_order' => $sort++,
            'source_snapshot' => $labor->only(['id', 'name', 'type', 'category', 'unit']),
        ]);

        $delivery = $materials['Material Delivery'];
        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'material_id' => $delivery->id,
            'item_type' => 'delivery',
            'name' => 'Material Delivery',
            'subtitle' => 'Delivery',
            'quantity' => 1,
            'unit' => 'trip',
            'unit_price_cents' => $delivery->selling_price_cents,
            'delivery_cost_cents' => $delivery->unit_cost_cents,
            'sort_order' => $sort,
            'source_snapshot' => $delivery->only(['id', 'name', 'type', 'category', 'unit']),
        ]);
    }

    private function attachEstimatePdf(Estimate $estimate, EstimatePdfService $pdfs, string $token): void
    {
        $path = $pdfs->generate($estimate);

        Attachment::create([
            'company_id' => $estimate->company_id,
            'attachable_type' => Estimate::class,
            'attachable_id' => $estimate->id,
            'original_filename' => basename($path),
            'display_name' => 'Estimate '.$estimate->estimate_number,
            'disk' => 'local',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'type' => AttachmentType::Document,
            'size_bytes' => Storage::disk('local')->size($path),
            'metadata' => ['review_url' => route('estimate-review.show', $token)],
        ]);
    }

    private function seedRecentActivity(Company $company, User $user): void
    {
        $events = [
            [ActivityEvent::EstimateEmailed, 'Davis estimate emailed with review link', now()->setTime(10, 0)],
            [ActivityEvent::EstimateApproved, 'Garcia estimate approved and signed', now()->setTime(11, 30)],
            [ActivityEvent::DepositRecorded, 'Lopez deposit recorded internally', now()->setTime(13, 0)],
            [ActivityEvent::PacketReady, 'Martinez job packet ready for handoff', now()->setTime(14, 30)],
            [ActivityEvent::LeadCreated, 'New lead from Website', now()->setTime(15, 15)],
            [ActivityEvent::ContractSigned, 'Smith signed estimate converted to job', now()->subDay()->setTime(16, 0)],
        ];

        foreach ($events as [$event, $description, $date]) {
            ActivityLog::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'event' => $event,
                'description' => $description,
                'metadata' => [],
                'created_at' => Carbon::parse($date),
                'updated_at' => Carbon::parse($date),
            ]);
        }
    }
}
