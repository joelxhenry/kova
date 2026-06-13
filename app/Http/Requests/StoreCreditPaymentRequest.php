<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StoreCreditPaymentRequest extends FormRequest
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
        $rules = [
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['required', 'integer', 'different:from_account_id', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            // For a one-off payment this is the posting date; for a recurring
            // payment it is the schedule's start date.
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'recurring' => ['boolean'],
        ];

        // A recurring payment becomes a scheduled transfer, so it needs a cadence
        // and may carry an optional end date.
        if ($this->boolean('recurring')) {
            $rules['frequency'] = ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly', 'yearly'])];
            $rules['end_date'] = ['nullable', 'date', 'after_or_equal:date'];
        } else {
            $rules['frequency'] = ['nullable'];
            $rules['end_date'] = ['nullable'];
        }

        return $rules;
    }

    /**
     * A payment must move money out of a cash (debit) account and into a credit
     * account; enforce the directions once both ids resolve to owned accounts.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Collection<int, Account> $accounts */
            $accounts = $this->user()->accounts()
                ->whereIn('id', array_filter([$this->input('from_account_id'), $this->input('to_account_id')]))
                ->get()
                ->keyBy('id');

            $from = $accounts->get((int) $this->input('from_account_id'));
            $to = $accounts->get((int) $this->input('to_account_id'));

            if ($from !== null && $from->type !== 'debit') {
                $validator->errors()->add('from_account_id', 'Payments must come from a cash (debit) account.');
            }

            if ($to !== null && $to->type !== 'credit') {
                $validator->errors()->add('to_account_id', 'You can only make a payment toward a credit account.');
            }
        });
    }
}
