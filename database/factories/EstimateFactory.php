<?php

namespace Database\Factories;

use App\Enums\EstimateStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Estimate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estimate>
 */
class EstimateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'estimate_number' => 'EST-'.now()->format('Y').'-'.fake()->unique()->numberBetween(100, 999),
            'project_name' => fake()->randomElement(['Backyard Renovation', 'Front Yard Refresh', 'Pool Area Upgrade']),
            'status' => fake()->randomElement(EstimateStatus::cases()),
            'overhead_basis_points' => 1000,
            'target_margin_basis_points' => 3000,
        ];
    }
}
