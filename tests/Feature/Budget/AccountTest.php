<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function makeAccount(User $user, array $attributes = []): Account
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

test('guests cannot access accounts', function () {
    $this->get('/budget/accounts')->assertRedirect('/login');
});

test('user can view accounts index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/budget/accounts')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Budget/Accounts/Index'));
});

test('create and edit pages render correct components', function () {
    $user = User::factory()->create();
    $account = makeAccount($user);

    $this->actingAs($user)
        ->get('/budget/accounts/create')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Budget/Accounts/Create'));

    $this->actingAs($user)
        ->get("/budget/accounts/{$account->id}/edit")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Accounts/Edit')
            ->where('account.id', $account->id));
});

test('current_balance initializes from opening_balance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', [
            'name' => 'Savings',
            'type' => 'debit',
            'opening_balance' => 2500.50,
            'is_active' => true,
        ])
        ->assertRedirect('/budget/accounts');

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Savings',
        'opening_balance' => 2500.50,
        'current_balance' => 2500.50,
    ]);
});

test('credit account stores interest rate and credit limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', [
            'name' => 'Visa Platinum',
            'type' => 'credit',
            'opening_balance' => 0,
            'interest_rate' => 19.99,
            'credit_limit' => 250000,
            'is_active' => true,
        ])
        ->assertRedirect('/budget/accounts');

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Visa Platinum',
        'type' => 'credit',
        'interest_rate' => 19.99,
        'credit_limit' => 250000,
    ]);
});

test('credit account can store an effective annual rate basis', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', [
            'name' => 'NCB Visa',
            'type' => 'credit',
            'opening_balance' => 0,
            'interest_rate' => 26.824,
            'rate_basis' => 'effective',
            'is_active' => true,
        ])
        ->assertRedirect('/budget/accounts');

    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'NCB Visa',
        'interest_rate' => 26.824,
        'rate_basis' => 'effective',
    ]);
});

test('rate basis must be a known value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', [
            'name' => 'Card',
            'type' => 'credit',
            'opening_balance' => 0,
            'interest_rate' => 20,
            'rate_basis' => 'weekly',
        ])
        ->assertSessionHasErrors('rate_basis');
});

test('rate basis defaults to apr when omitted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', [
            'name' => 'Card',
            'type' => 'credit',
            'opening_balance' => 0,
            'interest_rate' => 20,
            'is_active' => true,
        ])
        ->assertRedirect('/budget/accounts');

    $this->assertDatabaseHas('accounts', [
        'name' => 'Card',
        'rate_basis' => 'apr',
    ]);
});

test('interest rate cannot exceed 100 percent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', [
            'name' => 'Card',
            'type' => 'credit',
            'opening_balance' => 0,
            'interest_rate' => 150,
        ])
        ->assertSessionHasErrors('interest_rate');
});

test('interest rate and credit limit can be updated', function () {
    $user = User::factory()->create();
    $account = makeAccount($user, ['type' => 'credit', 'interest_rate' => 18, 'credit_limit' => 100000]);

    $this->actingAs($user)
        ->put("/budget/accounts/{$account->id}", [
            'name' => 'Card',
            'type' => 'credit',
            'opening_balance' => 0,
            'interest_rate' => 24.5,
            'credit_limit' => 300000,
            'is_active' => true,
        ])
        ->assertRedirect('/budget/accounts');

    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'interest_rate' => 24.5,
        'credit_limit' => 300000,
    ]);
});

test('account name and type are required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/accounts', ['name' => '', 'type' => 'invalid', 'opening_balance' => 0])
        ->assertSessionHasErrors(['name', 'type']);
});

test('updating opening balance re-derives current balance keeping ledger deltas', function () {
    $user = User::factory()->create();
    // opening 1000, but current shows a +200 ledger delta already applied.
    $account = makeAccount($user, ['opening_balance' => 1000, 'current_balance' => 1200]);

    $this->actingAs($user)
        ->put("/budget/accounts/{$account->id}", [
            'name' => 'Checking',
            'type' => 'debit',
            'opening_balance' => 1500,
            'is_active' => true,
        ])
        ->assertRedirect('/budget/accounts');

    // new current = old current 1200 + (1500 - 1000) = 1700
    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'opening_balance' => 1500,
        'current_balance' => 1700,
    ]);
});

test('transfer moves balance between accounts and is not income or expense', function () {
    $user = User::factory()->create();
    $from = makeAccount($user, ['name' => 'Checking', 'type' => 'debit', 'opening_balance' => 1000, 'current_balance' => 1000]);
    $to = makeAccount($user, ['name' => 'Savings', 'type' => 'debit', 'opening_balance' => 200, 'current_balance' => 200]);

    $this->actingAs($user)
        ->post('/budget/transfers', [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 300,
            'date' => '2026-06-01',
        ])
        ->assertRedirect('/budget/accounts');

    expect((float) $from->fresh()->current_balance)->toBe(700.0);
    expect((float) $to->fresh()->current_balance)->toBe(500.0);

    // Recorded as a single transfer row, never income/expense.
    $this->assertDatabaseHas('transactions', [
        'account_id' => $from->id,
        'transfer_account_id' => $to->id,
        'type' => 'transfer',
        'amount' => 300,
    ]);
    $this->assertDatabaseMissing('transactions', ['type' => 'income']);
    $this->assertDatabaseMissing('transactions', ['type' => 'expense']);
});

test('transfer cannot target the same account', function () {
    $user = User::factory()->create();
    $account = makeAccount($user);

    $this->actingAs($user)
        ->post('/budget/transfers', [
            'from_account_id' => $account->id,
            'to_account_id' => $account->id,
            'amount' => 50,
            'date' => '2026-06-01',
        ])
        ->assertSessionHasErrors('to_account_id');
});

test('user cannot edit another users account', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $account = makeAccount($owner);

    $this->actingAs($intruder)
        ->get("/budget/accounts/{$account->id}/edit")
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->put("/budget/accounts/{$account->id}", [
            'name' => 'Hacked',
            'type' => 'debit',
            'opening_balance' => 0,
            'is_active' => true,
        ])
        ->assertStatus(403);
});

test('user cannot transfer using another users account', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerAccount = makeAccount($owner, ['name' => 'Owner']);
    $intruderAccount = makeAccount($intruder, ['name' => 'Intruder']);

    $this->actingAs($intruder)
        ->post('/budget/transfers', [
            'from_account_id' => $ownerAccount->id,
            'to_account_id' => $intruderAccount->id,
            'amount' => 100,
            'date' => '2026-06-01',
        ])
        ->assertStatus(403);
});

test('account with transactions cannot be deleted', function () {
    $user = User::factory()->create();
    $account = makeAccount($user);
    $account->transactions()->create([
        'user_id' => $user->id,
        'type' => 'expense',
        'amount' => 10,
        'date' => '2026-06-01',
        'description' => 'Test',
    ]);

    $this->actingAs($user)
        ->delete("/budget/accounts/{$account->id}")
        ->assertRedirect('/budget/accounts')
        ->assertSessionHas('error');

    $this->assertDatabaseHas('accounts', ['id' => $account->id]);
});

test('account without transactions can be deleted', function () {
    $user = User::factory()->create();
    $account = makeAccount($user);

    $this->actingAs($user)
        ->delete("/budget/accounts/{$account->id}")
        ->assertRedirect('/budget/accounts');

    $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
});
