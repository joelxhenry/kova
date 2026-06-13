<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['debit', 'credit'])],
            'opening_balance' => ['required', 'numeric'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rate_basis' => ['nullable', Rule::in(['apr', 'effective'])],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
