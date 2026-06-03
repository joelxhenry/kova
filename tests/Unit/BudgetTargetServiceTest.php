<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\BudgetTarget;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\BudgetTargetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function targetService(): BudgetTargetService
{
    return app(BudgetTargetService::class);
}

/**
 * @param array<string, mixed> $attributes
 */
function makeTargetAccount(User $user, array $attributes = []): Account
{
    return Account::factory()->for($user)->create(array_merge([
        'name' => 'Checking',
        'type' => 'debit',
        'opening_balance' => 100000,
        'current_balance' => 100000,
    ], $attributes));
}

function makeCategory(string $name, string $kind = 'expense'): TransactionCategory
{
    return TransactionCategory::create([
        'user_id' => null,
        'name' => $name,
        'kind' => $kind,
        'is_default' => false,
        'sort_order' => 0,
    ]);
}

test('create persists a target and never posts a transaction', function () {
    $user = User::factory()->create();
    $category = makeCategory('Groceries');

    $target = targetService()->create($user, [
        'transaction_category_id' => $category->id,
        'type' => 'expense',
        'period' => 'monthly',
        'amount' => 30000,
    ]);

    expect($target)->toBeInstanceOf(BudgetTarget::class);
    expect((float) $target->amount)->toBe(30000.0);
    expect($target->user_id)->toBe($user->id);
    expect(Transaction::count())->toBe(0);
});

test('update changes the planned amount', function () {
    $user = User::factory()->create();
    $target = BudgetTarget::factory()->for($user)->create(['amount' => 10000]);

    targetService()->update($target, [
        'transaction_category_id' => $target->transaction_category_id,
        'type' => $target->type,
        'period' => 'monthly',
        'amount' => 25000,
    ]);

    expect((float) $target->fresh()->amount)->toBe(25000.0);
});

test('delete removes the target', function () {
    $user = User::factory()->create();
    $target = BudgetTarget::factory()->for($user)->create();

    targetService()->delete($target);

    $this->assertDatabaseMissing('budget_targets', ['id' => $target->id]);
});

test('report sums actuals and computes variance, percent and over flag (FR-6.2/6.3)', function () {
    $user = User::factory()->create();
    $account = makeTargetAccount($user);
    $groceries = makeCategory('Groceries');

    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $groceries->id,
        'type' => 'expense',
        'amount' => 30000,
    ]);

    // 12,000 + 6,000 = 18,000 spent against a 30,000 target this month.
    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $groceries->id,
        'amount' => 12000,
        'date' => '2026-06-10',
    ]);
    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $groceries->id,
        'amount' => 6000,
        'date' => '2026-06-20',
    ]);

    $report = targetService()->report($user, Carbon::createFromFormat('Y-m-d', '2026-06-15'));
    $row = collect($report['rows'])->firstWhere('category_id', $groceries->id);

    expect($row['planned'])->toBe(30000.0);
    expect($row['actual'])->toBe(18000.0);
    expect($row['variance'])->toBe(12000.0);
    expect($row['percent'])->toBe(60.0);
    expect($row['over'])->toBeFalse();
    expect($row['targeted'])->toBeTrue();

    expect($report['totals']['planned'])->toBe(30000.0);
    expect($report['totals']['actual'])->toBe(18000.0);
    expect($report['totals']['variance'])->toBe(12000.0);
});

test('report flags an over-budget category', function () {
    $user = User::factory()->create();
    $account = makeTargetAccount($user);
    $rent = makeCategory('Rent');

    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $rent->id,
        'type' => 'expense',
        'amount' => 20000,
    ]);

    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $rent->id,
        'amount' => 25000,
        'date' => '2026-06-05',
    ]);

    $report = targetService()->report($user, Carbon::createFromFormat('Y-m-d', '2026-06-15'));
    $row = collect($report['rows'])->firstWhere('category_id', $rent->id);

    expect($row['actual'])->toBe(25000.0);
    expect($row['over'])->toBeTrue();
    expect($row['variance'])->toBe(-5000.0);
    expect($row['percent'])->toBe(125.0);
});

test('transfers are excluded from actuals', function () {
    $user = User::factory()->create();
    $account = makeTargetAccount($user);
    $other = makeTargetAccount($user, ['name' => 'Savings']);
    $groceries = makeCategory('Groceries');

    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $groceries->id,
        'type' => 'expense',
        'amount' => 30000,
    ]);

    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $groceries->id,
        'amount' => 5000,
        'date' => '2026-06-10',
    ]);

    // A transfer carrying the same category must NOT count against the budget.
    Transaction::factory()->forAccount($account)->create([
        'transaction_category_id' => $groceries->id,
        'transfer_account_id' => $other->id,
        'type' => 'transfer',
        'amount' => 99999,
        'date' => '2026-06-11',
    ]);

    $report = targetService()->report($user, Carbon::createFromFormat('Y-m-d', '2026-06-15'));
    $row = collect($report['rows'])->firstWhere('category_id', $groceries->id);

    expect($row['actual'])->toBe(5000.0);
});

test('activity outside the selected month is excluded (FR-6.4)', function () {
    $user = User::factory()->create();
    $account = makeTargetAccount($user);
    $groceries = makeCategory('Groceries');

    BudgetTarget::factory()->for($user)->create([
        'transaction_category_id' => $groceries->id,
        'type' => 'expense',
        'amount' => 30000,
    ]);

    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $groceries->id,
        'amount' => 7000,
        'date' => '2026-06-15',
    ]);
    // Previous month — outside the window.
    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $groceries->id,
        'amount' => 4000,
        'date' => '2026-05-28',
    ]);

    $june = targetService()->report($user, Carbon::createFromFormat('Y-m-d', '2026-06-10'));
    $may = targetService()->report($user, Carbon::createFromFormat('Y-m-d', '2026-05-10'));

    expect(collect($june['rows'])->firstWhere('category_id', $groceries->id)['actual'])->toBe(7000.0);
    expect(collect($may['rows'])->firstWhere('category_id', $groceries->id)['actual'])->toBe(4000.0);
    expect($june['month'])->toBe('2026-06');
    expect($may['month'])->toBe('2026-05');
});

test('categories with activity but no target are surfaced', function () {
    $user = User::factory()->create();
    $account = makeTargetAccount($user);
    $entertainment = makeCategory('Entertainment');

    Transaction::factory()->forAccount($account)->expense()->create([
        'transaction_category_id' => $entertainment->id,
        'amount' => 3000,
        'date' => '2026-06-12',
    ]);

    $report = targetService()->report($user, Carbon::createFromFormat('Y-m-d', '2026-06-15'));
    $row = collect($report['rows'])->firstWhere('category_id', $entertainment->id);

    expect($row)->not->toBeNull();
    expect($row['targeted'])->toBeFalse();
    expect($row['planned'])->toBe(0.0);
    expect($row['actual'])->toBe(3000.0);
});
