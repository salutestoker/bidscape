<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'industry' => 'landscaping',
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'default_overhead_basis_points' => 1000,
            'default_target_margin_basis_points' => 3000,
            'default_price_sheet_markup_basis_points' => 3000,
        ];
    }
}
