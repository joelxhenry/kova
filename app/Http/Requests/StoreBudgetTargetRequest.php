<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetTargetRequest extends FormRequest
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
        $userId = $this->user()->id;
        $period = $this->input('period', 'monthly');

        return [
            // The category must be the user's own or a shared system default.
            'transaction_category_id' => [
                'required',
                'integer',
                Rule::exists('transaction_categories', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where(fn (Builder $q): Builder => $q->where('user_id', $userId)->orWhereNull('user_id')),
                ),
                // One target per [category, period] for this user.
                Rule::unique('budget_targets', 'transaction_category_id')->where(
                    fn (Builder $query): Builder => $query->where('user_id', $userId)->where('period', $period),
                ),
            ],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'period' => ['required', Rule::in(['monthly'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
