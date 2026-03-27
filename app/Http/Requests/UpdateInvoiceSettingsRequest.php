<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceSettingsRequest extends FormRequest
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
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'invoice_separator' => ['required', 'string', 'max:5'],
            'invoice_next_number' => ['required', 'integer', 'min:1'],
            'invoice_padding' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
