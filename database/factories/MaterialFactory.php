<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Material;
use App\Services\MoneyCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    public function definition(): array
    {
        $cost = fake()->numberBetween(75, 8500);
        $markup = fake()->numberBetween(2500, 5000);

        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('MAT-###-??')),
            'category' => fake()->randomElement(['Turf', 'Base', 'Pavers', 'Lighting', 'Plants']),
            'unit' => fake()->randomElement(['sqft', 'ton', 'each', 'lb', 'roll']),
            'unit_cost_cents' => $cost,
            'markup_basis_points' => $markup,
            'selling_price_cents' => MoneyCalculator::priceForMarkup($cost, $markup),
            'vendor' => fake()->company(),
        ];
    }
}
