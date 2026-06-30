<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Company;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'status' => LeadStatus::PendingContact,
            'project_interest' => fake()->sentence(),
            'site_notes' => fake()->sentence(),
            'next_follow_up_at' => now()->addDays(fake()->numberBetween(1, 10)),
        ];
    }
}
