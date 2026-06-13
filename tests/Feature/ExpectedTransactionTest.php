<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\ExpectedTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function makeExpectedAccount(User $user, array $attributes = []): Account
{
    return Account::factory()->for($user)->create(array_merge([
        'name' => 'Checking',
        'type' => 'debit',
        'opening_balance' => 1000,
        'current_balance' => 1000,
    ], $attributes));
}

test('guests cannot access expected items', function () {
    $this->get('/budget/expected')->assertRedirect('/login');
});

test('expected index renders with components and props', function () {
    $user = User::factory()->create();
    makeExpectedAccount($user);
    ExpectedTransaction::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/budget/expected')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Expected/Index')
            ->has('expected', 1)
            ->has('accounts', 1));
});

test('create and edit pages render correct components', function () {
    $user = User::factory()->create();
    $expected = ExpectedTransaction::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/budget/expected/create')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Budget/Expected/Create'));

    $this->actingAs($user)
        ->get("/budget/expected/{$expected->id}/edit")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Expected/Edit')
            ->where('expected.id', $expected->id));
});

test('creating an expected item does not change any account balance (FR-5.2)', function () {
    $user = User::factory()->create();
    $account = makeExpectedAccount($user, ['current_balance' => 1000]);

    $this->actingAs($user)
        ->post('/budget/expected', [
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 50000,
            'expected_date' => '2026-07-15',
            'description' => 'Client payment',
        ])
        ->assertRedirect('/budget/expected');

    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
    $this->assertDatabaseHas('expected_transactions', [
        'description' => 'Client payment',
        'status' => 'pending',
    ]);
    expect(Transaction::count())->toBe(0);
});

test('an expected item can be created without an account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/expected', [
            'account_id' => null,
            'type' => 'expense',
            'amount' => 20000,
            'expected_date' => '2026-08-01',
            'description' => 'Car repair',
        ])
        ->assertRedirect('/budget/expected');

    $this->assertDatabaseHas('expected_transactions', [
        'account_id' => null,
        'description' => 'Car repair',
    ]);
});

test('editing an expected item does not change any account balance (FR-5.2)', function () {
    $user = User::factory()->create();
    $account = makeExpectedAccount($user, ['current_balance' => 1000]);
    $expected = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 5000,
        'expected_date' => '2026-07-01',
    ]);

    $this->actingAs($user)
        ->put("/budget/expected/{$expected->id}", [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 9999,
            'expected_date' => '2026-07-01',
            'description' => 'Updated',
        ])
        ->assertRedirect('/budget/expected');

    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
    expect((float) $expected->fresh()->amount)->toBe(9999.0);
});

test('realize posts a transaction, adjusts balance and stamps provenance (FR-5.3)', function () {
    $user = User::factory()->create();
    $account = makeExpectedAccount($user, ['current_balance' => 1000]);
    $expected = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 50000,
        'expected_date' => '2026-07-15',
        'description' => 'Client payment',
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => $account->id,
            'date' => '2026-07-15',
            'amount' => 50000,
        ])
        ->assertRedirect('/budget/expected');

    // Income onto a debit account increases the balance.
    expect((float) $account->fresh()->current_balance)->toBe(51000.0);

    $expected->refresh();
    expect($expected->status)->toBe('realized');
    expect($expected->realized_transaction_id)->not->toBeNull();

    $this->assertDatabaseHas('transactions', [
        'id' => $expected->realized_transaction_id,
        'account_id' => $account->id,
        'type' => 'income',
        'description' => 'Client payment',
    ]);
});

test('realize override applies to the chosen account only', function () {
    $user = User::factory()->create();
    $stored = makeExpectedAccount($user, ['name' => 'Stored', 'current_balance' => 1000]);
    $other = makeExpectedAccount($user, ['name' => 'Other', 'current_balance' => 1000]);
    $expected = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $stored->id,
        'type' => 'expense',
        'amount' => 20000,
        'expected_date' => '2026-08-01',
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => $other->id,
            'date' => '2026-08-05',
            'amount' => 800,
        ])
        ->assertRedirect('/budget/expected');

    expect((float) $stored->fresh()->current_balance)->toBe(1000.0);
    expect((float) $other->fresh()->current_balance)->toBe(200.0);
    expect((int) $expected->fresh()->account_id)->toBe($other->id);
});

test('cannot realize an already-realized item', function () {
    $user = User::factory()->create();
    $account = makeExpectedAccount($user, ['current_balance' => 1000]);
    $expected = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 100,
        'status' => 'realized',
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => $account->id,
            'date' => '2026-07-01',
            'amount' => 100,
        ])
        ->assertRedirect('/budget/expected')
        ->assertSessionHas('error');

    // No new ledger row was posted and the balance is untouched.
    expect(Transaction::count())->toBe(0);
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
});

test('cannot realize a cancelled item', function () {
    $user = User::factory()->create();
    $account = makeExpectedAccount($user, ['current_balance' => 1000]);
    $expected = ExpectedTransaction::factory()->for($user)->cancelled()->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 100,
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => $account->id,
            'date' => '2026-07-01',
            'amount' => 100,
        ])
        ->assertSessionHas('error');

    expect(Transaction::count())->toBe(0);
});

test('realize requires an account', function () {
    $user = User::factory()->create();
    $expected = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => null,
        'type' => 'income',
        'amount' => 100,
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => null,
            'date' => '2026-07-01',
            'amount' => 100,
        ])
        ->assertSessionHasErrors('account_id');
});

test('cancel marks the item cancelled without a balance effect', function () {
    $user = User::factory()->create();
    $account = makeExpectedAccount($user, ['current_balance' => 1000]);
    $expected = ExpectedTransaction::factory()->for($user)->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 500,
    ]);

    $this->actingAs($user)
        ->post("/budget/expected/{$expected->id}/cancel")
        ->assertRedirect('/budget/expected');

    expect($expected->fresh()->status)->toBe('cancelled');
    expect((float) $account->fresh()->current_balance)->toBe(1000.0);
});

test('index can be filtered by status and type', function () {
    $user = User::factory()->create();
    ExpectedTransaction::factory()->for($user)->income()->create(['description' => 'PendingIncome']);
    ExpectedTransaction::factory()->for($user)->expense()->create(['description' => 'PendingExpense']);
    ExpectedTransaction::factory()->for($user)->cancelled()->expense()->create(['description' => 'Cancelled']);

    $this->actingAs($user)
        ->get('/budget/expected?status=pending')
        ->assertInertia(fn ($page) => $page->has('expected', 2));

    $this->actingAs($user)
        ->get('/budget/expected?type=income')
        ->assertInertia(fn ($page) => $page
            ->has('expected', 1)
            ->where('expected.0.description', 'PendingIncome'));
});

test('validation rejects bad input', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/budget/expected', [
            'account_id' => null,
            'type' => 'nonsense',
            'amount' => 0,
            'expected_date' => 'not-a-date',
            'description' => '',
        ])
        ->assertSessionHasErrors(['type', 'amount', 'expected_date', 'description']);
});

test('a user cannot create an expected item against another users account', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerAccount = makeExpectedAccount($owner);

    $this->actingAs($intruder)
        ->post('/budget/expected', [
            'account_id' => $ownerAccount->id,
            'type' => 'income',
            'amount' => 100,
            'expected_date' => '2026-07-01',
            'description' => 'Sneaky',
        ])
        ->assertSessionHasErrors('account_id');
});

test('a user cannot edit, update, realize or cancel another users item', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $intruderAccount = makeExpectedAccount($intruder, ['name' => 'Intruder']);
    $expected = ExpectedTransaction::factory()->for($owner)->create([
        'type' => 'expense',
        'amount' => 100,
    ]);

    $this->actingAs($intruder)
        ->get("/budget/expected/{$expected->id}/edit")
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->put("/budget/expected/{$expected->id}", [
            'account_id' => $intruderAccount->id,
            'type' => 'expense',
            'amount' => 999,
            'expected_date' => '2026-07-01',
            'description' => 'Hacked',
        ])
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->post("/budget/expected/{$expected->id}/realize", [
            'account_id' => $intruderAccount->id,
            'date' => '2026-07-01',
            'amount' => 100,
        ])
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->post("/budget/expected/{$expected->id}/cancel")
        ->assertStatus(403);
});
