<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:120'],
        ];
    }
}
