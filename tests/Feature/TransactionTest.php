<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function makeBudgetAccount(User $user, array $attributes = []): Account
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

test('guests cannot access transactions', function () {
    $this->get('/budget/transactions')->assertRedirect('/login');
});

test('transactions index renders with components and props', function () {
    $user = User::factory()->create();
    makeBudgetAccount($user);

    $this->actingAs($user)
        ->get('/budget/transactions')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Transactions/Index')
            ->has('transactions')
            ->has('accounts', 1)
            ->has('categories'));
});

test('create and edit pages render correct components', function () {
    $user = User::factory()->create();
    $account = makeBudgetAccount($user);
    $transaction = Transaction::create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 50,
        'date' => '2026-06-01',
        'description' => 'Lunch',
    ]);

    $this->actingAs($user)
        ->get('/budget/transactions/create')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Budget/Transactions/Create'));

    $this->actingAs($user)
        ->get("/budget/transactions/{$transaction->id}/edit")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Transactions/Edit')
            ->where('transaction.id', $transaction->id));
});

test('expense decrements a debit account balance (FR-2.3)', function () {
    $user = User::factory()->create();
    $account = makeBudgetAccount($user, ['current_balance' => 1000]);

    $this->actingAs($user)
        ->post('/budget/transactions', [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 250,
            'date' => '2026-06-01',
            'description' => 'Groceries',
        ])
        ->assertRedirect('/budget/transactions');

    expect((float) $account->fresh()->current_balance)->toBe(750.0);
});

test('income increments a debit account balance (FR-2.4)', function () {
    $user = User::factory()->create();
    $account = makeBudgetAccount($user, ['current_balance' => 1000]);

    $this->actingAs($user)
        ->post('/budget/transactions', [
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 500,
            'date' => '2026-06-01',
            'description' => 'Invoice paid',
        ])
        ->assertRedirect('/budget/transactions');

    expect((float) $account->fresh()->current_balance)->toBe(1500.0);
});

test('credit account sign rules: expense grows debt, income shrinks it', function () {
    $user = User::factory()->create();
    $card = makeBudgetAccount($user, ['type' => 'credit', 'opening_balance' => 0, 'current_balance' => 0]);

    // A charge on a credit card increases the balance owed.
    $this->actingAs($user)->post('/budget/transactions', [
        'account_id' => $card->id,
        'type' => 'expense',
        'amount' => 300,
        'date' => '2026-06-01',
        'description' => 'Online purchase',
    ])->assertRedirect();

    expect((float) $card->fresh()->current_balance)->toBe(300.0);

    // A payment (income) reduces the balance owed.
    $this->actingAs($user)->post('/budget/transactions', [
        'account_id' => $card->id,
        'type' => 'income',
        'amount' => 100,
        'date' => '2026-06-02',
        'description' => 'Card payment',
    ])->assertRedirect();

    expect((float) $card->fresh()->current_balance)->toBe(200.0);
});

test('editing a transaction re-derives the current balance', function () {
    $user = User::factory()->create();
    $account = makeBudgetAccount($user, ['current_balance' => 1000]);

    $this->actingAs($user)->post('/budget/transactions', [
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 200,
        'date' => '2026-06-01',
        'description' => 'Initial',
    ])->assertRedirect();

    expect((float) $account->fresh()->current_balance)->toBe(800.0);

    $transaction = Transaction::firstOrFail();

    // Raise the expense to 500: balance should be 1000 - 500 = 500.
    $this->actingAs($user)->put("/budget/transactions/{$transaction->id}", [
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 500,
        'date' => '2026-06-01',
        'description' => 'Initial',
    ])->assertRedirect();

    expect((float) $account->fresh()->current_balance)->toBe(500.0);
});

test('changing a transaction account moves the balance effect', function () {
    $user = User::factory()->create();
    $a = makeBudgetAccount($user, ['name' => 'A', 'current_balance' => 1000]);
    $b = makeBudgetAccount($user, ['name' => 'B', 'current_balance' => 1000]);

    $this->actingAs($user)->post('/budget/transactions', [
        'account_id' => $a->id,
        'type' => 'expense',
        'amount' => 200,
        'date' => '2026-06-01',
        'description' => 'Move me',
    ])->assertRedirect();

    expect((float) $a->fresh()->current_balance)->toBe(800.0);

    $transaction = Transaction::firstOrFail();

    $this->actingAs($user)->put("/budget/transactions/{$transaction->id}", [
        'account_id' => $b->id,
        'type' => 'expense',
        'amount' => 200,
        'date' => '2026-06-01',
        'description' => 'Move me',
    ])->assertRedirect();

    // A is restored, B now bears the expense.
    expect((float) $a->fresh()->current_balance)->toBe(1000.0);
    expect((float) $b->fresh()->current_balance)->toBe(800.0);
});

test('deleting a transaction reverses the balance effect', function () {
    $user = User::factory()->create();
    $account = makeBudgetAccount($user, ['current_balance' => 1000]);

    $this->actingAs($user)->post('/budget/transactions', [
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 400,
        'date' => '2026-06-01',
        'description' => 'Refundable',
    ])->assertRedirect();

    expect((float) $account->fresh()->current_balance)->toBe(1400.0);

    $transaction = Transaction::firstOrFail();

    $this->actingAs($user)
        ->delete("/budget/transactions/{$transaction->id}")
        ->assertRedirect('/budget/transactions');

    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
    $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
});

test('ledger returns transactions across all accounts with working filters', function () {
    $user = User::factory()->create();
    $checking = makeBudgetAccount($user, ['name' => 'Checking']);
    $savings = makeBudgetAccount($user, ['name' => 'Savings']);

    Transaction::create(['user_id' => $user->id, 'account_id' => $checking->id, 'type' => 'expense', 'amount' => 10, 'date' => '2026-06-01', 'description' => 'C1']);
    Transaction::create(['user_id' => $user->id, 'account_id' => $savings->id, 'type' => 'income', 'amount' => 20, 'date' => '2026-06-02', 'description' => 'S1']);
    // A transfer row must never appear in the income/expense ledger.
    Transaction::create(['user_id' => $user->id, 'account_id' => $checking->id, 'transfer_account_id' => $savings->id, 'type' => 'transfer', 'amount' => 5, 'date' => '2026-06-03', 'description' => 'Xfer']);

    // No filter → both income/expense rows across both accounts, transfer excluded.
    $this->actingAs($user)
        ->get('/budget/transactions')
        ->assertInertia(fn ($page) => $page->has('transactions.data', 2));

    // Filter by account.
    $this->actingAs($user)
        ->get("/budget/transactions?account_id={$savings->id}")
        ->assertInertia(fn ($page) => $page
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'S1'));

    // Filter by type.
    $this->actingAs($user)
        ->get('/budget/transactions?type=expense')
        ->assertInertia(fn ($page) => $page
            ->has('transactions.data', 1)
            ->where('transactions.data.0.description', 'C1'));
});

test('a custom category can be created and assigned to a transaction', function () {
    $user = User::factory()->create();
    $account = makeBudgetAccount($user);

    // forUser exposes both the seeded defaults and the user's own categories.
    $custom = TransactionCategory::create([
        'user_id' => $user->id,
        'name' => 'Side Hustle',
        'kind' => 'income',
        'is_default' => false,
        'sort_order' => 100,
    ]);

    $available = TransactionCategory::forUser($user->id);
    expect($available->contains($custom))->toBeTrue();
    expect($available->firstWhere('name', 'Salary'))->not->toBeNull();

    $this->actingAs($user)->post('/budget/transactions', [
        'account_id' => $account->id,
        'type' => 'income',
        'transaction_category_id' => $custom->id,
        'amount' => 750,
        'date' => '2026-06-01',
        'description' => 'Gig',
    ])->assertRedirect('/budget/transactions');

    $this->assertDatabaseHas('transactions', [
        'transaction_category_id' => $custom->id,
        'description' => 'Gig',
    ]);
});

test('validation rejects bad input', function () {
    $user = User::factory()->create();
    makeBudgetAccount($user);

    $this->actingAs($user)
        ->post('/budget/transactions', [
            'account_id' => null,
            'type' => 'transfer',
            'amount' => 0,
            'date' => 'not-a-date',
            'description' => '',
        ])
        ->assertSessionHasErrors(['account_id', 'type', 'amount', 'date', 'description']);
});

test('a user cannot post a transaction against another users account', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerAccount = makeBudgetAccount($owner);

    $this->actingAs($intruder)
        ->post('/budget/transactions', [
            'account_id' => $ownerAccount->id,
            'type' => 'expense',
            'amount' => 10,
            'date' => '2026-06-01',
            'description' => 'Sneaky',
        ])
        ->assertSessionHasErrors('account_id');
});

test('a user cannot edit or update another users transaction', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerAccount = makeBudgetAccount($owner);
    $intruderAccount = makeBudgetAccount($intruder, ['name' => 'Intruder']);
    $transaction = Transaction::create([
        'user_id' => $owner->id,
        'account_id' => $ownerAccount->id,
        'type' => 'expense',
        'amount' => 25,
        'date' => '2026-06-01',
        'description' => 'Private',
    ]);

    $this->actingAs($intruder)
        ->get("/budget/transactions/{$transaction->id}/edit")
        ->assertStatus(403);

    // Uses the intruder's own account so validation passes and the
    // controller's ownership guard is what rejects the request.
    $this->actingAs($intruder)
        ->put("/budget/transactions/{$transaction->id}", [
            'account_id' => $intruderAccount->id,
            'type' => 'expense',
            'amount' => 999,
            'date' => '2026-06-01',
            'description' => 'Hacked',
        ])
        ->assertStatus(403);
});
