<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaxProfileRequest extends FormRequest
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
            'trn' => ['nullable', 'string', 'max:20', 'regex:/^\d{9}$/'],
            'business_type' => ['required', Rule::in([
                'specified_services',
                'construction',
                'haulage',
                'tillage',
                'other',
            ])],
            'is_gct_registered' => ['required', 'boolean'],
            'gct_registration_date' => ['nullable', 'date', 'required_if:is_gct_registered,true'],
            'fiscal_year_start' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trn.regex' => 'TRN must be exactly 9 digits.',
            'gct_registration_date.required_if' => 'GCT registration date is required when GCT registered.',
        ];
    }
}
