<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingsRequest extends FormRequest
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
            'invoice_email_subject' => ['required', 'string', 'max:255'],
            'invoice_email_greeting' => ['required', 'string', 'max:500'],
            'invoice_email_body' => ['required', 'string', 'max:2000'],
            'invoice_email_footer' => ['required', 'string', 'max:1000'],
            'invoice_email_include_payment_instructions' => ['required', 'boolean'],
        ];
    }
}
