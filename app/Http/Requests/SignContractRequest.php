<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
