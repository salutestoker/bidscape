<?php

namespace Tests\Feature;

use App\Models\Assembly;
use App\Models\Estimate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_primary_authenticated_pages_render_successfully(): void
    {
        $this->seed();

        $user = User::where('email', 'nick@desertridge.test')->firstOrFail();
        $estimate = Estimate::firstOrFail();
        $job = Job::firstOrFail();
        $assembly = Assembly::firstOrFail();

        $routes = [
            '/dashboard',
            '/leads',
            '/customers',
            '/estimates',
            "/estimates/{$estimate->id}/builder",
            '/jobs',
            "/jobs/{$job->id}/packet",
            '/assemblies',
            "/assemblies/{$assembly->id}/formula",
            '/materials',
            '/reports',
            '/settings',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->get($route)->assertOk();
        }
    }
}
