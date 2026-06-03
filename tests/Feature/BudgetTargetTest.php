<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\BudgetTarget;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function makeTargetTestAccount(User $user, array $attributes = []): Account
{
    return Account::factory()->for($user)->create(array_merge([
        'name' => 'Checking',
        'type' => 'debit',
        'opening_balance' => 100000,
        'current_balance' => 100000,
    ], $attributes));
}

function makeTargetCategory(string $name, string $kind = 'expense'): TransactionCategory
{
    return TransactionCategory::create([
        'user_id' => null,
        'name' => $name,
        'kind' => $kind,
        'is_default' => false,
        'sort_order' => 0,
    ]);
}

test('guests cannot access budget targets', function () {
    $this->get('/budget/targets')->assertRedirect('/login');
});

test('targets index renders the report with components and props', function () {
    $user = User::factory()->create();
    $account = makeTargetTestAccount($user);
    $category = makeTargetCategory('Groceries');
    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $category->id,
        'type' => 'expense',
        'amount' => 30000,
    ]);
    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $category->id,
        'amount' => 5000,
        'date' => '2026-06-10',
    ]);

    $this->actingAs($user)
        ->get('/budget/targets?month=2026-06')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Targets/Index')
            ->where('month', '2026-06')
            ->has('targets', 1)
            ->has('report.rows', 1)
            ->where('report.rows.0.actual', 5000));
});

test('create and edit pages render correct components', function () {
    $user = User::factory()->create();
    $target = BudgetTarget::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/budget/targets/create')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Budget/Targets/Create'));

    $this->actingAs($user)
        ->get("/budget/targets/{$target->id}/edit")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Targets/Edit')
            ->where('target.id', $target->id));
});

test('a target can be created', function () {
    $user = User::factory()->create();
    $category = makeTargetCategory('Groceries');

    $this->actingAs($user)
        ->post('/budget/targets', [
            'transaction_category_id' => $category->id,
            'type' => 'expense',
            'period' => 'monthly',
            'amount' => 30000,
        ])
        ->assertRedirect('/budget/targets');

    $this->assertDatabaseHas('budget_targets', [
        'user_id' => $user->id,
        'transaction_category_id' => $category->id,
        'type' => 'expense',
        'period' => 'monthly',
    ]);
});

test('only one target per category and period is allowed (unique rule)', function () {
    $user = User::factory()->create();
    $category = makeTargetCategory('Groceries');

    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $category->id,
        'type' => 'expense',
        'period' => 'monthly',
        'amount' => 10000,
    ]);

    $this->actingAs($user)
        ->post('/budget/targets', [
            'transaction_category_id' => $category->id,
            'type' => 'expense',
            'period' => 'monthly',
            'amount' => 20000,
        ])
        ->assertSessionHasErrors('transaction_category_id');

    expect(BudgetTarget::where('transaction_category_id', $category->id)->count())->toBe(1);
});

test('two users can target the same shared default category independently', function () {
    $shared = makeTargetCategory('Groceries');
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    BudgetTarget::factory()->for($alice)->create([
        'transaction_category_id' => $shared->id,
        'type' => 'expense',
        'amount' => 10000,
    ]);

    $this->actingAs($bob)
        ->post('/budget/targets', [
            'transaction_category_id' => $shared->id,
            'type' => 'expense',
            'period' => 'monthly',
            'amount' => 15000,
        ])
        ->assertRedirect('/budget/targets');

    expect(BudgetTarget::where('transaction_category_id', $shared->id)->count())->toBe(2);
});

test('a target can be updated', function () {
    $user = User::factory()->create();
    $target = BudgetTarget::factory()->for($user)->create(['amount' => 10000]);

    $this->actingAs($user)
        ->put("/budget/targets/{$target->id}", [
            'transaction_category_id' => $target->transaction_category_id,
            'type' => $target->type,
            'period' => 'monthly',
            'amount' => 25000,
        ])
        ->assertRedirect('/budget/targets');

    expect((float) $target->fresh()->amount)->toBe(25000.0);
});

test('a target can be deleted', function () {
    $user = User::factory()->create();
    $target = BudgetTarget::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/budget/targets/{$target->id}")
        ->assertRedirect('/budget/targets');

    $this->assertDatabaseMissing('budget_targets', ['id' => $target->id]);
});

test('changing the selected month moves the report window (FR-6.4)', function () {
    $user = User::factory()->create();
    $account = makeTargetTestAccount($user);
    $category = makeTargetCategory('Groceries');
    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $category->id,
        'type' => 'expense',
        'amount' => 30000,
    ]);

    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $category->id,
        'amount' => 8000,
        'date' => '2026-06-10',
    ]);
    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $category->id,
        'amount' => 2000,
        'date' => '2026-05-10',
    ]);

    $this->actingAs($user)
        ->get('/budget/targets?month=2026-06')
        ->assertInertia(fn ($page) => $page
            ->where('month', '2026-06')
            ->where('report.rows.0.actual', 8000));

    $this->actingAs($user)
        ->get('/budget/targets?month=2026-05')
        ->assertInertia(fn ($page) => $page
            ->where('month', '2026-05')
            ->where('report.rows.0.actual', 2000));
});

test('validation rejects bad input', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/targets', [
            'transaction_category_id' => null,
            'type' => 'transfer',
            'period' => 'weekly',
            'amount' => 0,
        ])
        ->assertSessionHasErrors(['transaction_category_id', 'type', 'period', 'amount']);
});

test('the reports page omits budget adherence when no targets exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('budgetAdherence', null));
});

test('the reports page surfaces current-month budget adherence when targets exist', function () {
    $user = User::factory()->create();
    $account = makeTargetTestAccount($user);
    $category = makeTargetCategory('Groceries');
    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $category->id,
        'type' => 'expense',
        'amount' => 30000,
    ]);
    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $category->id,
        'amount' => 9000,
        'date' => now()->startOfMonth()->format('Y-m-d'),
    ]);

    $this->actingAs($user)
        ->get('/reports')
        ->assertInertia(fn ($page) => $page
            ->where('budgetAdherence.month', now()->format('Y-m'))
            ->has('budgetAdherence.rows', 1)
            ->where('budgetAdherence.rows.0.actual', 9000)
            ->where('budgetAdherence.rows.0.targeted', true));
});

test('a user cannot edit, update or delete another users target', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $target = BudgetTarget::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->get("/budget/targets/{$target->id}/edit")
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->put("/budget/targets/{$target->id}", [
            'transaction_category_id' => $target->transaction_category_id,
            'type' => 'expense',
            'period' => 'monthly',
            'amount' => 999,
        ])
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->delete("/budget/targets/{$target->id}")
        ->assertStatus(403);
});
