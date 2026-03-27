<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithholdingCreditRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'date_withheld' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
