<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TransactionService(new AccountService());
    $this->user = User::factory()->create();
});

/**
 * @param array<string, mixed> $attributes
 */
function unitAccount(User $user, array $attributes = []): Account
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

test('create applies a signed delta and persists the row', function () {
    $account = unitAccount($this->user, ['current_balance' => 1000]);

    $transaction = $this->service->create($this->user, [
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 150,
        'date' => '2026-06-01',
        'description' => 'Test',
    ]);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect((float) $account->fresh()->current_balance)->toBe(850.0);
});

test('update reverses the old delta before applying the new one', function () {
    $account = unitAccount($this->user, ['current_balance' => 1000]);

    $transaction = $this->service->create($this->user, [
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 150,
        'date' => '2026-06-01',
        'description' => 'Test',
    ]);

    // Flip type to income and change amount: 1000 + 300 = 1300.
    $this->service->update($transaction, [
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 300,
        'date' => '2026-06-01',
        'description' => 'Test',
    ]);

    expect((float) $account->fresh()->current_balance)->toBe(1300.0);
});

test('cached balance equals opening plus recomputed ledger deltas (no drift)', function () {
    $account = unitAccount($this->user, ['opening_balance' => 1000, 'current_balance' => 1000]);

    $this->service->create($this->user, ['account_id' => $account->id, 'type' => 'income', 'amount' => 500, 'date' => '2026-06-01', 'description' => 'A']);
    $this->service->create($this->user, ['account_id' => $account->id, 'type' => 'expense', 'amount' => 200, 'date' => '2026-06-02', 'description' => 'B']);
    $this->service->create($this->user, ['account_id' => $account->id, 'type' => 'expense', 'amount' => 50, 'date' => '2026-06-03', 'description' => 'C']);

    // Recompute from the ledger using the same sign helper the service relies on.
    $recomputed = (float) $account->opening_balance;
    foreach ($account->transactions()->whereIn('type', ['income', 'expense'])->get() as $t) {
        $signed = $t->type === 'income' ? (float) $t->amount : -(float) $t->amount;
        $recomputed += $signed;
    }

    expect((float) $account->fresh()->current_balance)->toBe($recomputed);
    expect((float) $account->fresh()->current_balance)->toBe(1250.0);
});
