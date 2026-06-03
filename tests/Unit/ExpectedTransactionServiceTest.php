<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\ExpectedTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ExpectedTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

function expectedService(): ExpectedTransactionService
{
    return app(ExpectedTransactionService::class);
}

/**
 * @param array<string, mixed> $attributes
 */
function makeServiceAccount(User $user, array $attributes = []): Account
{
    return Account::factory()->for($user)->create(array_merge([
        'name' => 'Checking',
        'type' => 'debit',
        'opening_balance' => 1000,
        'current_balance' => 1000,
    ], $attributes));
}

test('create persists a pending item and never moves a balance (FR-5.2)', function () {
    $user = User::factory()->create();
    $account = makeServiceAccount($user, ['current_balance' => 1000]);

    $item = expectedService()->create($user, [
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 50000,
        'expected_date' => '2026-07-15',
        'description' => 'Client payment',
    ]);

    expect($item->status)->toBe('pending');
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
    expect(Transaction::count())->toBe(0);
});

test('realize posts a ledger transaction and stamps provenance (FR-5.3)', function () {
    $user = User::factory()->create();
    $account = makeServiceAccount($user, ['current_balance' => 1000]);
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 300,
        'expected_date' => '2026-07-01',
        'description' => 'Repair',
    ]);

    $transaction = expectedService()->realize($item);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect((float) $account->fresh()->current_balance)->toBe(700.0);

    $item->refresh();
    expect($item->status)->toBe('realized');
    expect($item->realized_transaction_id)->toBe($transaction->id);
});

test('realize honours an account, date and amount override', function () {
    $user = User::factory()->create();
    $stored = makeServiceAccount($user, ['name' => 'Stored', 'current_balance' => 1000]);
    $other = makeServiceAccount($user, ['name' => 'Other', 'current_balance' => 1000]);
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $stored->id,
        'type' => 'expense',
        'amount' => 300,
        'expected_date' => '2026-07-01',
    ]);

    $transaction = expectedService()->realize($item, [
        'account_id' => $other->id,
        'date' => '2026-07-05',
        'amount' => 200,
    ]);

    expect((float) $stored->fresh()->current_balance)->toBe(1000.0);
    expect((float) $other->fresh()->current_balance)->toBe(800.0);
    expect((float) $transaction->amount)->toBe(200.0);
    expect($transaction->date->toDateString())->toBe('2026-07-05');
});

test('realize throws when the item is not pending', function () {
    $user = User::factory()->create();
    $account = makeServiceAccount($user);
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'status' => 'realized',
    ]);

    expect(fn () => expectedService()->realize($item))
        ->toThrow(RuntimeException::class);
});

test('realize throws when no account is resolvable', function () {
    $user = User::factory()->create();
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => null,
        'type' => 'income',
        'amount' => 100,
    ]);

    expect(fn () => expectedService()->realize($item))
        ->toThrow(RuntimeException::class);

    expect(Transaction::count())->toBe(0);
});

test('cancel marks the item cancelled with no balance effect', function () {
    $user = User::factory()->create();
    $account = makeServiceAccount($user, ['current_balance' => 1000]);
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 500,
    ]);

    expectedService()->cancel($item);

    expect($item->fresh()->status)->toBe('cancelled');
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
});

test('delete removes the item without a balance effect', function () {
    $user = User::factory()->create();
    $account = makeServiceAccount($user, ['current_balance' => 1000]);
    $item = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
    ]);

    expectedService()->delete($item);

    $this->assertDatabaseMissing('expected_transactions', ['id' => $item->id]);
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
});
