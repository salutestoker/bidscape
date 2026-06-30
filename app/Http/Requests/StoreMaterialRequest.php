<?php

namespace App\Http\Requests;

use App\Enums\MaterialType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(MaterialType::class)],
            'sku' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:120'],
            'unit' => ['required', 'string', 'max:40'],
            'unit_cost_cents' => ['required', 'integer', 'min:0'],
            'markup_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'hourly_rate_cents' => ['nullable', 'integer', 'min:0'],
            'minimum_charge_cents' => ['nullable', 'integer', 'min:0'],
            'pricing_method' => ['nullable', 'string', 'max:120'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
