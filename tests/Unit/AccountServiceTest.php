<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function buildAccount(User $user, array $attributes = []): Account
{
    return $user->accounts()->create(array_merge([
        'name' => 'Account',
        'type' => 'debit',
        'opening_balance' => 1000,
        'current_balance' => 1000,
        'is_active' => true,
        'sort_order' => 0,
    ], $attributes));
}

beforeEach(function () {
    $this->service = new AccountService();
    $this->user = User::factory()->create();
});

test('create seeds current_balance from opening_balance', function () {
    $account = $this->service->create($this->user, [
        'name' => 'Cash',
        'type' => 'debit',
        'opening_balance' => 750,
        'is_active' => true,
    ]);

    expect((float) $account->current_balance)->toBe(750.0);
});

test('applyDelta on debit account: income adds, expense subtracts', function () {
    $account = buildAccount($this->user, ['type' => 'debit', 'current_balance' => 1000]);

    $this->service->applyDelta($account, 200, 'income');
    expect((float) $account->fresh()->current_balance)->toBe(1200.0);

    $this->service->applyDelta($account, 500, 'expense');
    expect((float) $account->fresh()->current_balance)->toBe(700.0);
});

test('applyDelta on credit account: expense grows debt, income shrinks it', function () {
    $account = buildAccount($this->user, ['type' => 'credit', 'opening_balance' => 0, 'current_balance' => 0]);

    // A charge increases the outstanding balance (debt).
    $this->service->applyDelta($account, 300, 'expense');
    expect((float) $account->fresh()->current_balance)->toBe(300.0);

    // A payment reduces it.
    $this->service->applyDelta($account, 100, 'income');
    expect((float) $account->fresh()->current_balance)->toBe(200.0);
});

test('update recalculates current_balance preserving ledger deltas', function () {
    // 1000 opening with a +250 ledger delta already baked into current.
    $account = buildAccount($this->user, ['opening_balance' => 1000, 'current_balance' => 1250]);

    $this->service->update($account, ['opening_balance' => 1800]);

    // current = 1250 + (1800 - 1000) = 2050
    expect((float) $account->fresh()->current_balance)->toBe(2050.0);
});

test('update without opening_balance leaves current_balance untouched', function () {
    $account = buildAccount($this->user, ['opening_balance' => 1000, 'current_balance' => 1250]);

    $this->service->update($account, ['name' => 'Renamed']);

    expect((float) $account->fresh()->current_balance)->toBe(1250.0);
    expect($account->fresh()->name)->toBe('Renamed');
});

test('transfer between debit accounts moves balance and records a transfer row', function () {
    $from = buildAccount($this->user, ['type' => 'debit', 'current_balance' => 1000]);
    $to = buildAccount($this->user, ['type' => 'debit', 'current_balance' => 500]);

    $transaction = $this->service->transfer($from, $to, [
        'amount' => 400,
        'date' => '2026-06-01',
    ]);

    expect($transaction->type)->toBe('transfer');
    expect((float) $from->fresh()->current_balance)->toBe(600.0);
    expect((float) $to->fresh()->current_balance)->toBe(900.0);
});

test('transfer from debit to credit pays down the credit balance', function () {
    $checking = buildAccount($this->user, ['type' => 'debit', 'current_balance' => 1000]);
    $card = buildAccount($this->user, ['type' => 'credit', 'current_balance' => 800]);

    $this->service->transfer($checking, $card, [
        'amount' => 300,
        'date' => '2026-06-01',
    ]);

    // Debit source drops; credit destination (income leg) shrinks the debt.
    expect((float) $checking->fresh()->current_balance)->toBe(700.0);
    expect((float) $card->fresh()->current_balance)->toBe(500.0);
});

test('available_credit reflects limit minus outstanding balance on credit accounts', function () {
    $card = buildAccount($this->user, [
        'type' => 'credit',
        'current_balance' => 30000,
        'credit_limit' => 100000,
    ]);

    expect($card->available_credit)->toBe(70000.0);
});

test('available_credit is null for debit accounts or when no limit is set', function () {
    $checking = buildAccount($this->user, ['type' => 'debit', 'current_balance' => 5000]);
    expect($checking->available_credit)->toBeNull();

    $card = buildAccount($this->user, ['type' => 'credit', 'current_balance' => 5000]);
    expect($card->available_credit)->toBeNull();
});

test('estimated_monthly_interest divides an APR across twelve months', function () {
    $card = buildAccount($this->user, [
        'type' => 'credit',
        'current_balance' => 120000,
        'interest_rate' => 24,
        'rate_basis' => 'apr',
    ]);

    // 120000 * (24 / 100) / 12 = 2400
    expect($card->estimated_monthly_interest)->toBe(2400.0);
});

test('estimated_monthly_interest un-compounds an effective annual rate', function () {
    // An EAR of 26.824% compounds from a 2% monthly rate, so a 120000 balance
    // accrues ~2400/month — less than naively dividing the EAR by 12 (2682).
    $card = buildAccount($this->user, [
        'type' => 'credit',
        'current_balance' => 120000,
        'interest_rate' => 26.824,
        'rate_basis' => 'effective',
    ]);

    expect($card->estimated_monthly_interest)->toEqualWithDelta(2400.0, 1.0);
});

test('estimated_monthly_interest is null when no rate is set', function () {
    $account = buildAccount($this->user, ['current_balance' => 1000]);
    expect($account->estimated_monthly_interest)->toBeNull();
});

test('effective_annual_rate compounds an APR up to its EAR', function () {
    // 24% APR compounded monthly = (1 + 0.24/12)^12 - 1 = 26.824%.
    $card = buildAccount($this->user, ['interest_rate' => 24, 'rate_basis' => 'apr']);
    expect($card->effective_annual_rate)->toEqualWithDelta(26.824, 0.01);
});

test('effective_annual_rate passes an effective rate through unchanged', function () {
    $card = buildAccount($this->user, ['interest_rate' => 26.824, 'rate_basis' => 'effective']);
    expect($card->effective_annual_rate)->toBe(26.824);
});

test('delete throws when transactions exist', function () {
    $account = buildAccount($this->user);
    $account->transactions()->create([
        'user_id' => $this->user->id,
        'type' => 'expense',
        'amount' => 10,
        'date' => '2026-06-01',
        'description' => 'Test',
    ]);

    expect(fn () => $this->service->delete($account))
        ->toThrow(RuntimeException::class);
});
