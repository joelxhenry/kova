<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\RecurringTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $account = Account::factory()->create();
        $start = fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d');

        return [
            'user_id' => $account->user_id,
            'account_id' => $account->id,
            'transfer_account_id' => null,
            'transaction_category_id' => null,
            'type' => fake()->randomElement(['income', 'expense']),
            'amount' => (float) fake()->randomFloat(2, 500, 50000),
            'frequency' => fake()->randomElement(['daily', 'weekly', 'biweekly', 'monthly', 'yearly']),
            'start_date' => $start,
            'end_date' => null,
            // A fresh rule's first run is its start date.
            'next_run_date' => $start,
            'last_run_date' => null,
            'description' => fake()->sentence(3),
            'is_active' => true,
        ];
    }

    /**
     * Attach the rule to a specific account (and its owner).
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $account->user_id,
            'account_id' => $account->id,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
