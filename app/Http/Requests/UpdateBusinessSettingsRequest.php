<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingsRequest extends FormRequest
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
            'business_name' => ['nullable', 'string', 'max:255'],
            'business_address_line_1' => ['nullable', 'string', 'max:255'],
            'business_address_line_2' => ['nullable', 'string', 'max:255'],
            'business_city' => ['nullable', 'string', 'max:255'],
            'business_state_or_parish' => ['nullable', 'string', 'max:255'],
            'business_postal_code' => ['nullable', 'string', 'max:20'],
            'business_country' => ['nullable', 'string', 'max:100'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:1000'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,svg', 'max:2048'],
        ];
    }
}
