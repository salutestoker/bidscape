<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssemblyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'markup_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'waste_factor_basis_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'base_depth_inches' => ['nullable', 'numeric', 'min:0'],
            'labor_hours_per_unit' => ['nullable', 'numeric', 'min:0'],
            'default_minutes_per_unit' => ['nullable', 'numeric', 'min:0'],
            'production_rate_per_day' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
