<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $opening = (float) fake()->randomFloat(2, 5000, 250000);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Checking', 'Savings', 'Cash Wallet', 'Money Market']),
            'type' => 'debit',
            'opening_balance' => $opening,
            // Cached balance starts equal to the opening balance (FR-1.x).
            'current_balance' => $opening,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * A credit (liability) account.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Credit Card',
            'type' => 'credit',
        ]);
    }

    /**
     * A deactivated account.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
