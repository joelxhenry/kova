<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExpectedTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpectedTransaction>
 */
class ExpectedTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => null,
            'transaction_category_id' => null,
            'type' => fake()->randomElement(['income', 'expense']),
            'amount' => (float) fake()->randomFloat(2, 1000, 100000),
            'expected_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'description' => fake()->randomElement(['Client payment', 'Car repair', 'Bonus', 'Insurance renewal']),
            'notes' => null,
            'status' => 'pending',
            'realized_transaction_id' => null,
        ];
    }

    /**
     * An anticipated income item.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'income']);
    }

    /**
     * An anticipated expense item.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'expense']);
    }

    /**
     * A planned payment (transfer) toward a credit account. Callers supply the
     * funding `account_id` and the credit `transfer_account_id`.
     */
    public function payment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'transfer',
            'description' => 'Credit card payment',
        ]);
    }

    /**
     * A cancelled item, retained for history.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'cancelled']);
    }
}
