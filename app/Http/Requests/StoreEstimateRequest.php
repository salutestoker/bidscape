<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id', 'required_without:lead_id'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'project_name' => ['required', 'string', 'max:255'],
            'scope_summary' => ['nullable', 'string'],
        ];
    }
}
