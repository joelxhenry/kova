<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('demo seeder provisions a coherent budgeting dataset', function () {
    $this->seed(DemoSeeder::class);

    $user = User::where('email', 'demo@kova.test')->firstOrFail();

    expect($user->accounts()->count())->toBe(3);
    expect($user->transactions()->whereIn('type', ['income', 'expense'])->count())->toBeGreaterThan(0);
    expect($user->recurringTransactions()->count())->toBe(3);

    // Balances were applied through the services, so the checking account's
    // cached balance reflects its seeded ledger entries (250000 + 180000 − 28500).
    $checking = $user->accounts()->where('name', 'NCB Checking')->firstOrFail();
    expect((float) $checking->current_balance)->toBe(401500.0);

    // A credit card charge grows the debt, a payment shrinks it (48000 + 15600 − 30000).
    $card = $user->accounts()->where('name', 'Scotiabank Credit Card')->firstOrFail();
    expect((float) $card->current_balance)->toBe(33600.0);

    // No recurring rule has run yet (no scheduler invocation in the seeder).
    expect(Transaction::whereNotNull('recurring_transaction_id')->count())->toBe(0);
    expect(RecurringTransaction::where('is_active', true)->count())->toBe(3);
});
