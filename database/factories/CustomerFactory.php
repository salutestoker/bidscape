<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('480-555-0###'),
            'site_address' => fake()->streetAddress(),
            'city' => 'Mesa',
            'state' => 'AZ',
            'postal_code' => fake()->postcode(),
            'last_activity_at' => now(),
        ];
    }
}
