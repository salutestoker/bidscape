<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'title' => $user->title,
                    'company_id' => $user->company_id,
                ] : null,
                'company' => $user?->company ? [
                    'id' => $user->company->id,
                    'name' => $user->company->name,
                    'industry' => $user->company->industry,
                    'brand_primary_color' => $user->company->brand_primary_color,
                    'logo_url' => $user->company->logo_path ? route('settings.company-logo', $user->company) : null,
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'toast' => $request->session()->get('toast'),
            ],
        ];
    }
}
