<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('account factory seeds a coherent debit account', function () {
    $account = Account::factory()->create();

    expect($account->type)->toBe('debit')
        ->and($account->is_active)->toBeTrue()
        // Cached balance starts equal to the opening balance.
        ->and((float) $account->current_balance)->toBe((float) $account->opening_balance);
});

test('account factory credit and inactive states apply', function () {
    expect(Account::factory()->credit()->create()->type)->toBe('credit');
    expect(Account::factory()->inactive()->create()->is_active)->toBeFalse();
});

test('transaction factory keeps user and account ownership aligned', function () {
    $transaction = Transaction::factory()->create();

    expect($transaction->user_id)->toBe($transaction->account->user_id)
        ->and($transaction->type)->toBeIn(['income', 'expense']);
});

test('transaction factory forAccount binds to a given account', function () {
    $account = Account::factory()->create();
    $transaction = Transaction::factory()->forAccount($account)->expense()->create();

    expect($transaction->account_id)->toBe($account->id)
        ->and($transaction->user_id)->toBe($account->user_id)
        ->and($transaction->type)->toBe('expense');
});

test('recurring transaction factory seeds an active rule with next_run_date = start_date', function () {
    $rule = RecurringTransaction::factory()->create();

    expect($rule->is_active)->toBeTrue()
        ->and($rule->next_run_date->toDateString())->toBe($rule->start_date->toDateString())
        ->and($rule->user_id)->toBe($rule->account->user_id);
});
