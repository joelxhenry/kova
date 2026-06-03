<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BudgetTarget;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetTarget>
 */
class BudgetTargetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // A fresh shared (system-default) category keeps the unique
            // [user_id, category, period] constraint collision-free per call.
            'transaction_category_id' => fn (): int => TransactionCategory::query()->create([
                'user_id' => null,
                'name' => fake()->unique()->words(2, true),
                'kind' => 'both',
                'is_default' => false,
                'sort_order' => 0,
            ])->id,
            'type' => 'expense',
            'period' => 'monthly',
            'amount' => (float) fake()->randomFloat(2, 5000, 100000),
        ];
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
