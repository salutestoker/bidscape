<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\JobStatus;
use App\Enums\LeadStatus;
use App\Enums\MaterialType;
use App\Models\Assembly;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_leads_index_sorts_by_whitelisted_columns_and_exposes_status_colors(): void
    {
        [$company, $user] = $this->companyUser();
        $source = LeadSource::create(['company_id' => $company->id, 'name' => 'Website', 'channel' => 'web']);

        Lead::factory()->create([
            'company_id' => $company->id,
            'lead_source_id' => $source->id,
            'name' => 'Zeta Lead',
            'status' => LeadStatus::PendingContact,
        ]);
        Lead::factory()->create([
            'company_id' => $company->id,
            'lead_source_id' => $source->id,
            'name' => 'Alpha Lead',
            'status' => LeadStatus::SiteVisit,
            'site_visit_scheduled_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get('/leads?sort=name&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Leads/Index')
                ->where('leads.data.0.name', 'Alpha Lead')
                ->where('leads.data.0.status_color', 'blue')
                ->where('statuses.0.color', 'neutral')
            );
    }

    public function test_invalid_sort_params_fall_back_to_default_order(): void
    {
        [$company, $user] = $this->companyUser();

        Lead::factory()->create([
            'company_id' => $company->id,
            'name' => 'Old Lead',
            'created_at' => now()->subDay(),
        ]);
        Lead::factory()->create([
            'company_id' => $company->id,
            'name' => 'New Lead',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/leads?sort=unsafe_column&direction=desc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Leads/Index')
                ->where('leads.data.0.name', 'New Lead')
            );
    }

    public function test_customers_index_sorts_by_name(): void
    {
        [$company, $user] = $this->companyUser();

        Customer::factory()->create(['company_id' => $company->id, 'name' => 'Zeta Customer']);
        Customer::factory()->create(['company_id' => $company->id, 'name' => 'Alpha Customer']);

        $this->actingAs($user)
            ->get('/customers?sort=name&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Index')
                ->where('customers.data.0.name', 'Alpha Customer')
            );
    }

    public function test_estimates_index_sorts_by_project(): void
    {
        [$company, $user] = $this->companyUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        Estimate::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'project_name' => 'Zeta Patio']);
        Estimate::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id, 'project_name' => 'Alpha Turf']);

        $this->actingAs($user)
            ->get('/estimates?sort=project&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Estimates/Index')
                ->where('estimates.data.0.project', 'Alpha Turf')
            );
    }

    public function test_jobs_index_sorts_by_contract_value(): void
    {
        [$company, $user] = $this->companyUser();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $lowEstimate = Estimate::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $highEstimate = Estimate::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $lowContract = $this->contract($company, $customer, $lowEstimate, 'CON-LOW');
        $highContract = $this->contract($company, $customer, $highEstimate, 'CON-HIGH');

        Job::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'estimate_id' => $lowEstimate->id,
            'contract_id' => $lowContract->id,
            'job_number' => 'JOB-LOW',
            'project_name' => 'Low Job',
            'status' => JobStatus::Sold,
            'contract_value_cents' => 1000,
            'accepted_snapshot' => ['items' => []],
        ]);
        Job::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'estimate_id' => $highEstimate->id,
            'contract_id' => $highContract->id,
            'job_number' => 'JOB-HIGH',
            'project_name' => 'High Job',
            'status' => JobStatus::Sold,
            'contract_value_cents' => 9000,
            'accepted_snapshot' => ['items' => []],
        ]);

        $this->actingAs($user)
            ->get('/jobs?sort=contract_value_cents&direction=desc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Jobs/Index')
                ->where('jobs.data.0.number', 'JOB-HIGH')
            );
    }

    public function test_materials_and_assemblies_sort_by_name(): void
    {
        [$company, $user] = $this->companyUser();

        Material::factory()->create(['company_id' => $company->id, 'name' => 'Zeta Rock', 'type' => MaterialType::PhysicalMaterial]);
        Material::factory()->create(['company_id' => $company->id, 'name' => 'Alpha Turf', 'type' => MaterialType::PhysicalMaterial]);
        Assembly::factory()->create(['company_id' => $company->id, 'name' => 'Zeta Assembly']);
        Assembly::factory()->create(['company_id' => $company->id, 'name' => 'Alpha Assembly']);

        $this->actingAs($user)
            ->get('/materials?sort=name&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Materials/Index')
                ->where('materials.data.0.name', 'Alpha Turf')
            );

        $this->actingAs($user)
            ->get('/assemblies?sort=name&direction=asc')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assemblies/Index')
                ->where('assemblies.data.0.name', 'Alpha Assembly')
            );
    }

    public function test_flash_toast_is_shared_with_inertia_props(): void
    {
        [, $user] = $this->companyUser();

        $this->actingAs($user)
            ->withSession(['toast' => ['message' => 'Saved cleanly.', 'type' => 'success']])
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('flash.toast.message', 'Saved cleanly.')
                ->where('flash.toast.type', 'success')
            );
    }

    /**
     * @return array{Company, User}
     */
    private function companyUser(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        return [$company, $user];
    }

    private function contract(Company $company, Customer $customer, Estimate $estimate, string $number): Contract
    {
        return Contract::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'estimate_id' => $estimate->id,
            'contract_number' => $number,
            'status' => ContractStatus::Signed,
            'total_cents' => 1000,
            'accepted_snapshot' => ['items' => []],
        ]);
    }
}
