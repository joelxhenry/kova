<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create the owning account up front so user_id and account_id always
        // belong to the same user (callers may override via forAccount()).
        $account = Account::factory()->create();

        return [
            'user_id' => $account->user_id,
            'account_id' => $account->id,
            'transfer_account_id' => null,
            'transaction_category_id' => null,
            'type' => fake()->randomElement(['income', 'expense']),
            'amount' => (float) fake()->randomFloat(2, 100, 25000),
            'date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'description' => fake()->sentence(3),
            'notes' => null,
            'recurring_transaction_id' => null,
        ];
    }

    /**
     * Attach the transaction to a specific account (and its owner).
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $account->user_id,
            'account_id' => $account->id,
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'income']);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'expense']);
    }
}
