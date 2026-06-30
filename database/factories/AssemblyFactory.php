<?php

namespace Database\Factories;

use App\Models\Assembly;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assembly>
 */
class AssemblyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['Ground Cover', 'Hardscape', 'Irrigation', 'Lighting', 'Plants']),
            'unit' => fake()->randomElement(['sqft', 'zone', 'fixture', 'qty']),
            'description' => fake()->sentence(),
            'markup_basis_points' => 3000,
            'overhead_basis_points' => 1000,
            'target_margin_basis_points' => 3000,
            'labor_hours_per_unit' => '0.018',
            'base_cost_cents' => 728,
            'selling_price_cents' => 1005,
        ];
    }
}
