<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringTransactionRequest extends FormRequest
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
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'transfer_account_id' => [
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'transaction_category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'description' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
