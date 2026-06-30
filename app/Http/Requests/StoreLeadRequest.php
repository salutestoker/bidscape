<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'lead_source_id' => ['required', 'integer', 'exists:lead_sources,id'],
            'site_address' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'contact_preference' => ['nullable', 'string', 'max:80'],
            'project_interest' => ['nullable', 'string'],
            'requested_project_specifications' => ['nullable', 'string'],
            'site_notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'gate_code' => ['nullable', 'string', 'max:255'],
            'site_visit_scheduled_at' => ['nullable', 'date'],
            'next_follow_up_at' => ['nullable', 'date'],
        ];
    }
}
