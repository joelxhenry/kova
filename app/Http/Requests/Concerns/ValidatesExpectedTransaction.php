<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\Account;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Shared validation for storing/updating expected items. An expected item is a
 * planned income, expense, or — when type=transfer — a payment funded from a
 * debit account into a credit account.
 */
trait ValidatesExpectedTransaction
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function expectedRules(): array
    {
        return [
            // Funding/source account. Optional for income/expense, required for a
            // transfer (enforced in withValidator).
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            // Destination account for a planned payment (type=transfer).
            'transfer_account_id' => [
                'nullable',
                'integer',
                'different:account_id',
                Rule::exists('accounts', 'id')->where('user_id', $this->user()->id),
            ],
            'transaction_category_id' => ['nullable', 'integer', 'exists:transaction_categories,id'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expected_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A planned payment must move money from a debit account into a credit one.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== 'transfer') {
                return;
            }

            if ($this->input('account_id') === null) {
                $validator->errors()->add('account_id', 'Choose the cash account the payment comes from.');
            }

            if ($this->input('transfer_account_id') === null) {
                $validator->errors()->add('transfer_account_id', 'Choose the credit account to pay.');
            }

            /** @var Collection<int, Account> $accounts */
            $accounts = $this->user()->accounts()
                ->whereIn('id', array_filter([$this->input('account_id'), $this->input('transfer_account_id')]))
                ->get()
                ->keyBy('id');

            $from = $accounts->get((int) $this->input('account_id'));
            $to = $accounts->get((int) $this->input('transfer_account_id'));

            if ($from !== null && $from->type !== 'debit') {
                $validator->errors()->add('account_id', 'Payments must come from a cash (debit) account.');
            }

            if ($to !== null && $to->type !== 'credit') {
                $validator->errors()->add('transfer_account_id', 'You can only plan a payment toward a credit account.');
            }
        });
    }
}
