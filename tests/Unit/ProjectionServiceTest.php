<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\ExpectedTransaction;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ProjectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-06-01');
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * @param array<string, mixed> $attributes
 */
function makeProjectionAccount(User $user, array $attributes = []): Account
{
    return $user->accounts()->create(array_merge([
        'name' => 'Checking',
        'type' => 'debit',
        'opening_balance' => 1000,
        'current_balance' => 1000,
        'is_active' => true,
        'sort_order' => 0,
    ], $attributes));
}

/**
 * @param array<string, mixed> $attributes
 */
function makeProjectionRule(User $user, Account $account, array $attributes = []): RecurringTransaction
{
    return $user->recurringTransactions()->create(array_merge([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 100,
        'frequency' => 'monthly',
        'start_date' => '2026-06-15',
        'next_run_date' => '2026-06-15',
        'description' => 'Rent',
        'is_active' => true,
    ], $attributes));
}

function projectionService(): ProjectionService
{
    return app(ProjectionService::class);
}

test('projected balance = current balance plus applied recurring deltas to target (FR-4.1)', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);
    makeProjectionRule($user, $account, ['type' => 'expense', 'amount' => 100, 'frequency' => 'monthly', 'next_run_date' => '2026-06-15']);

    // Horizon to 2026-09-01: occurrences on Jun 15, Jul 15, Aug 15 → −300.
    $result = projectionService()->project($user, Carbon::parse('2026-09-01'));

    $points = $result['datasets'][0]['points'];
    expect(end($points))->toBe(700.0);
    expect($result['ending_net_worth'])->toBe(700.0);
    expect($result['starting_net_worth'])->toBe(1000.0);
});

test('income increases and credit-account rules follow their sign rules', function () {
    $user = User::factory()->create();
    $debit = makeProjectionAccount($user, ['name' => 'Checking', 'current_balance' => 1000]);
    $credit = makeProjectionAccount($user, ['name' => 'Card', 'type' => 'credit', 'current_balance' => 500]);

    // Salary income onto debit (+200), charge onto credit grows debt (+50).
    makeProjectionRule($user, $debit, ['type' => 'income', 'amount' => 200, 'next_run_date' => '2026-06-10']);
    makeProjectionRule($user, $credit, ['type' => 'expense', 'amount' => 50, 'next_run_date' => '2026-06-10']);

    $result = projectionService()->project($user, Carbon::parse('2026-06-20'));

    $byName = collect($result['datasets'])->keyBy('name');
    $checking = $byName['Checking']['points'];
    $card = $byName['Card']['points'];
    expect(end($checking))->toBe(1200.0);
    expect(end($card))->toBe(550.0);

    // Net worth = debit (1200) − credit debt (550) = 650.
    expect($result['ending_net_worth'])->toBe(650.0);
});

test('credit-account interest compounds monthly into the projection (B9)', function () {
    $user = User::factory()->create();
    // 12% APR → 1% per month. No recurring activity, so the balance compounds.
    makeProjectionAccount($user, [
        'name' => 'Card',
        'type' => 'credit',
        'current_balance' => 100000,
        'interest_rate' => 12,
        'rate_basis' => 'apr',
    ]);

    // Horizon to today + 12 months → 12 monthly accruals.
    $result = projectionService()->project($user, Carbon::parse('2026-06-01')->addMonthsNoOverflow(12));

    $card = collect($result['datasets'])->firstWhere('name', 'Card');
    // 100000 * 1.01^12 ≈ 112682.50.
    expect(end($card['points']))->toEqualWithDelta(112682.50, 1.0);
    expect($card['interest_accrued'])->toEqualWithDelta(12682.50, 1.0);
    expect($result['interest']['cost'])->toEqualWithDelta(12682.50, 1.0);
    expect($result['interest']['earned'])->toBe(0.0);
    expect($result['interest']['net_worth_impact'])->toEqualWithDelta(-12682.50, 1.0);
});

test('an effective-rate account compounds back up to its stated annual rate', function () {
    $user = User::factory()->create();
    // 26.824% EAR compounds from ~2%/month, so 12 months ≈ +26.824%.
    makeProjectionAccount($user, [
        'name' => 'EAR Card',
        'type' => 'credit',
        'current_balance' => 100000,
        'interest_rate' => 26.824,
        'rate_basis' => 'effective',
    ]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-01')->addMonthsNoOverflow(12));

    $card = collect($result['datasets'])->firstWhere('name', 'EAR Card');
    expect(end($card['points']))->toEqualWithDelta(126824.0, 2.0);
});

test('accounts without a rate accrue no interest and report none', function () {
    $user = User::factory()->create();
    makeProjectionAccount($user, ['name' => 'Plain', 'current_balance' => 5000]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-01')->addMonthsNoOverflow(6));

    $plain = collect($result['datasets'])->firstWhere('name', 'Plain');
    expect(end($plain['points']))->toBe(5000.0);
    expect($plain['interest_accrued'])->toBe(0.0);
    expect($result['interest']['by_account'])->toHaveCount(0);
    expect($result['interest']['cost'])->toBe(0.0);
});

test('interest accrual performs no database writes', function () {
    $user = User::factory()->create();
    $card = makeProjectionAccount($user, [
        'type' => 'credit', 'current_balance' => 50000, 'interest_rate' => 24, 'rate_basis' => 'apr',
    ]);

    projectionService()->project($user, Carbon::parse('2026-12-31'));

    expect((float) $card->fresh()->current_balance)->toBe(50000.0);
    expect(Transaction::count())->toBe(0);
});

test('account filtering changes the returned series (FR-4.3)', function () {
    $user = User::factory()->create();
    $a = makeProjectionAccount($user, ['name' => 'A', 'current_balance' => 1000]);
    $b = makeProjectionAccount($user, ['name' => 'B', 'current_balance' => 1000]);
    makeProjectionRule($user, $a, ['type' => 'expense', 'amount' => 100, 'next_run_date' => '2026-06-10']);

    $all = projectionService()->project($user, Carbon::parse('2026-06-20'));
    $onlyB = projectionService()->project($user, Carbon::parse('2026-06-20'), [$b->id]);

    expect($all['datasets'])->toHaveCount(2);
    expect($onlyB['datasets'])->toHaveCount(1);
    expect($onlyB['datasets'][0]['name'])->toBe('B');

    // A bears the −100 expense; filtering it out leaves B flat at 1000.
    expect(end($all['aggregate']))->toBe(1900.0);
    expect(end($onlyB['aggregate']))->toBe(1000.0);
});

test('below-zero debit projection produces an alert with the breach date (FR-4.5)', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 100]);
    makeProjectionRule($user, $account, ['type' => 'expense', 'amount' => 200, 'next_run_date' => '2026-06-10']);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    expect($result['alerts'])->toHaveCount(1);
    expect($result['alerts'][0]['account_id'])->toBe($account->id);
    expect($result['alerts'][0]['date'])->toBe('2026-06-10');
    expect($result['alerts'][0]['balance'])->toBe(-100.0);
});

test('a credit account going negative does NOT trigger a debit breach alert', function () {
    $user = User::factory()->create();
    $card = makeProjectionAccount($user, ['type' => 'credit', 'current_balance' => 100]);
    // Payment (income) on a credit account shrinks debt below zero (overpayment).
    makeProjectionRule($user, $card, ['type' => 'income', 'amount' => 200, 'next_run_date' => '2026-06-10']);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    expect($result['alerts'])->toHaveCount(0);
});

test('projection performs no database writes (read-only simulation)', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);
    $rule = makeProjectionRule($user, $account, ['type' => 'expense', 'amount' => 100, 'next_run_date' => '2026-06-15']);

    projectionService()->project($user, Carbon::parse('2026-12-31'));

    expect(Transaction::count())->toBe(0);
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
    expect($rule->fresh()->next_run_date->toDateString())->toBe('2026-06-15');
    expect($rule->fresh()->last_run_date)->toBeNull();
});

test('inactive accounts are excluded from the default projection', function () {
    $user = User::factory()->create();
    makeProjectionAccount($user, ['name' => 'Active', 'current_balance' => 1000]);
    makeProjectionAccount($user, ['name' => 'Closed', 'current_balance' => 5000, 'is_active' => false]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    expect($result['datasets'])->toHaveCount(1);
    expect($result['datasets'][0]['name'])->toBe('Active');
});

test('a recurring transfer moves balance between tracked accounts without affecting net worth', function () {
    $user = User::factory()->create();
    $from = makeProjectionAccount($user, ['name' => 'Checking', 'current_balance' => 1000]);
    $to = makeProjectionAccount($user, ['name' => 'Savings', 'current_balance' => 0]);

    $user->recurringTransactions()->create([
        'account_id' => $from->id,
        'transfer_account_id' => $to->id,
        'type' => 'transfer',
        'amount' => 300,
        'frequency' => 'monthly',
        'start_date' => '2026-06-10',
        'next_run_date' => '2026-06-10',
        'description' => 'Auto-save',
        'is_active' => true,
    ]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-20'));

    $byName = collect($result['datasets'])->keyBy('name');
    $checking = $byName['Checking']['points'];
    $savings = $byName['Savings']['points'];
    expect(end($checking))->toBe(700.0);
    expect(end($savings))->toBe(300.0);
    // Net worth unchanged by an internal transfer between two debit accounts.
    $aggregate = $result['aggregate'];
    expect(end($aggregate))->toBe(1000.0);
});

test('a pending account-bound expected item is folded into the projection (FR-5.4)', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);
    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 5000,
        'expected_date' => '2026-06-15',
        'status' => 'pending',
    ]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    $points = $result['datasets'][0]['points'];
    expect(end($points))->toBe(6000.0);
    expect($result['ending_net_worth'])->toBe(6000.0);
    expect($result['expected_events'])->toHaveCount(1);
    expect($result['expected_events'][0]['account_id'])->toBe($account->id);
    expect($result['expected_events'][0]['date'])->toBe('2026-06-15');
});

test('an unassigned pending expected item moves only the aggregate (FR-5.4)', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);
    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => null,
        'type' => 'expense',
        'amount' => 400,
        'expected_date' => '2026-06-10',
        'status' => 'pending',
    ]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    // The single account series is untouched; the aggregate absorbs the expense.
    $points = $result['datasets'][0]['points'];
    expect(end($points))->toBe(1000.0);
    expect(end($result['aggregate']))->toBe(600.0);
    expect($result['expected_events'][0]['account_id'])->toBeNull();
});

test('realized and cancelled expected items are excluded from the projection (FR-5.5)', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);

    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 5000,
        'expected_date' => '2026-06-15',
        'status' => 'realized',
    ]);
    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 7000,
        'expected_date' => '2026-06-16',
        'status' => 'cancelled',
    ]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    $points = $result['datasets'][0]['points'];
    expect(end($points))->toBe(1000.0);
    expect($result['expected_events'])->toHaveCount(0);
});

test('expected items outside the horizon are excluded', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);
    // Before today and after the horizon — both ignored.
    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id, 'type' => 'income', 'amount' => 100, 'expected_date' => '2026-05-01', 'status' => 'pending',
    ]);
    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id, 'type' => 'income', 'amount' => 100, 'expected_date' => '2026-12-01', 'status' => 'pending',
    ]);

    $result = projectionService()->project($user, Carbon::parse('2026-06-30'));

    $points = $result['datasets'][0]['points'];
    expect(end($points))->toBe(1000.0);
    expect($result['expected_events'])->toHaveCount(0);
});

test('an account-bound expected item under a per-account filter only shows for the tracked account', function () {
    $user = User::factory()->create();
    $a = makeProjectionAccount($user, ['name' => 'A', 'current_balance' => 1000]);
    $b = makeProjectionAccount($user, ['name' => 'B', 'current_balance' => 1000]);
    ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $a->id, 'type' => 'expense', 'amount' => 300, 'expected_date' => '2026-06-10', 'status' => 'pending',
    ]);

    // Filtering to B excludes A's expected item.
    $onlyB = projectionService()->project($user, Carbon::parse('2026-06-30'), [$b->id]);
    expect(end($onlyB['datasets'][0]['points']))->toBe(1000.0);
    expect($onlyB['expected_events'])->toHaveCount(0);

    // Filtering to A includes it.
    $onlyA = projectionService()->project($user, Carbon::parse('2026-06-30'), [$a->id]);
    expect(end($onlyA['datasets'][0]['points']))->toBe(700.0);
    expect($onlyA['expected_events'])->toHaveCount(1);
});

test('projection with expected items still performs no database writes', function () {
    $user = User::factory()->create();
    $account = makeProjectionAccount($user, ['current_balance' => 1000]);
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id, 'type' => 'income', 'amount' => 5000, 'expected_date' => '2026-06-15', 'status' => 'pending',
    ]);

    projectionService()->project($user, Carbon::parse('2026-12-31'));

    expect(Transaction::count())->toBe(0);
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
    expect($item->fresh()->status)->toBe('pending');
});
