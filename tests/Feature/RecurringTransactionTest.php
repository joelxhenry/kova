<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param array<string, mixed> $attributes
 */
function makeRecurringAccount(User $user, array $attributes = []): Account
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
function makeRule(User $user, Account $account, array $attributes = []): RecurringTransaction
{
    return app(RecurringTransactionService::class)->create($user, array_merge([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 100,
        'frequency' => 'monthly',
        'start_date' => '2026-06-01',
        'description' => 'Rent',
    ], $attributes));
}

function recurringService(): RecurringTransactionService
{
    return app(RecurringTransactionService::class);
}

// ---------------------------------------------------------------------------
// HTTP / CRUD / authorization
// ---------------------------------------------------------------------------

test('guests cannot access recurring rules', function () {
    $this->get('/budget/recurring')->assertRedirect('/login');
});

test('recurring index renders with components and props', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    makeRule($user, $account);

    $this->actingAs($user)
        ->get('/budget/recurring')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Recurring/Index')
            ->has('recurring', 1));
});

test('create and edit pages render correct components', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account);

    $this->actingAs($user)
        ->get('/budget/recurring/create')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Recurring/Create')
            ->has('accounts', 1)
            ->has('categories'));

    $this->actingAs($user)
        ->get("/budget/recurring/{$rule->id}/edit")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Recurring/Edit')
            ->where('recurring.id', $rule->id));
});

test('storing a rule seeds next_run_date from start_date', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);

    $this->actingAs($user)
        ->post('/budget/recurring', [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 200,
            'frequency' => 'monthly',
            'start_date' => '2026-07-01',
            'description' => 'Subscription',
        ])
        ->assertRedirect('/budget/recurring');

    $rule = RecurringTransaction::firstOrFail();
    expect($rule->next_run_date->toDateString())->toBe('2026-07-01');
    expect($rule->is_active)->toBeTrue();
    expect($rule->last_run_date)->toBeNull();
});

test('validation rejects bad input', function () {
    $user = User::factory()->create();
    makeRecurringAccount($user);

    $this->actingAs($user)
        ->post('/budget/recurring', [
            'account_id' => null,
            'type' => 'nope',
            'amount' => 0,
            'frequency' => 'fortnightly',
            'start_date' => 'not-a-date',
            'end_date' => '2020-01-01',
            'description' => '',
        ])
        ->assertSessionHasErrors(['account_id', 'type', 'amount', 'frequency', 'start_date', 'description']);
});

test('end date before start date is rejected', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);

    $this->actingAs($user)
        ->post('/budget/recurring', [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 50,
            'frequency' => 'monthly',
            'start_date' => '2026-06-01',
            'end_date' => '2026-05-01',
            'description' => 'Bad range',
        ])
        ->assertSessionHasErrors('end_date');
});

test('a user cannot edit, update, or cancel another users rule', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerAccount = makeRecurringAccount($owner);
    $intruderAccount = makeRecurringAccount($intruder, ['name' => 'Intruder']);
    $rule = makeRule($owner, $ownerAccount);

    $this->actingAs($intruder)
        ->get("/budget/recurring/{$rule->id}/edit")
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->put("/budget/recurring/{$rule->id}", [
            'account_id' => $intruderAccount->id,
            'type' => 'expense',
            'amount' => 999,
            'frequency' => 'monthly',
            'start_date' => '2026-06-01',
            'description' => 'Hacked',
        ])
        ->assertStatus(403);

    $this->actingAs($intruder)
        ->post("/budget/recurring/{$rule->id}/cancel")
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// Generation engine (FR-3.2 / FR-3.3)
// ---------------------------------------------------------------------------

test('generateDue creates a transaction on the trigger date and advances per frequency', function (string $frequency, string $expectedNext) {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account, [
        'frequency' => $frequency,
        'start_date' => '2026-06-01',
    ]);

    $generated = recurringService()->generateDue(Carbon::parse('2026-06-01'));

    expect($generated)->toBe(1);

    $rule->refresh();
    expect($rule->next_run_date->toDateString())->toBe($expectedNext);
    expect($rule->last_run_date->toDateString())->toBe('2026-06-01');

    $this->assertDatabaseHas('transactions', [
        'recurring_transaction_id' => $rule->id,
        'date' => '2026-06-01 00:00:00',
        'amount' => '100.00',
    ]);
})->with([
    'daily' => ['daily', '2026-06-02'],
    'weekly' => ['weekly', '2026-06-08'],
    'biweekly' => ['biweekly', '2026-06-15'],
    'monthly' => ['monthly', '2026-07-01'],
    'yearly' => ['yearly', '2027-06-01'],
]);

test('generated transactions adjust the account balance', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user, ['current_balance' => 1000]);
    makeRule($user, $account, ['type' => 'expense', 'amount' => 150, 'frequency' => 'monthly', 'start_date' => '2026-06-01']);

    recurringService()->generateDue(Carbon::parse('2026-06-01'));

    expect((float) $account->fresh()->current_balance)->toBe(850.0);
});

test('a rule is not generated before its start date', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account, ['start_date' => '2026-07-01']);

    $generated = recurringService()->generateDue(Carbon::parse('2026-06-15'));

    expect($generated)->toBe(0);
    expect($account->fresh()->current_balance)->toBe('1000.00');
    expect($rule->fresh()->next_run_date->toDateString())->toBe('2026-07-01');
});

test('catch-up generates every missed occurrence without duplicates', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user, ['current_balance' => 1000]);
    $rule = makeRule($user, $account, [
        'type' => 'expense',
        'amount' => 100,
        'frequency' => 'monthly',
        'start_date' => '2026-01-15',
    ]);

    // Jan 15, Feb 15, Mar 15, Apr 15, May 15 are due by Jun 1 (Jun 15 is not).
    $generated = recurringService()->generateDue(Carbon::parse('2026-06-01'));

    expect($generated)->toBe(5);
    expect(Transaction::where('recurring_transaction_id', $rule->id)->count())->toBe(5);
    expect($rule->fresh()->next_run_date->toDateString())->toBe('2026-06-15');
    expect((float) $account->fresh()->current_balance)->toBe(500.0);

    // Idempotent: a second run within the same window adds nothing.
    $again = recurringService()->generateDue(Carbon::parse('2026-06-01'));
    expect($again)->toBe(0);
    expect(Transaction::where('recurring_transaction_id', $rule->id)->count())->toBe(5);
    expect((float) $account->fresh()->current_balance)->toBe(500.0);
});

test('month-end recurrence clamps to shorter months', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account, ['frequency' => 'monthly', 'start_date' => '2026-01-31']);

    // Jan 31 generates, next clamps to Feb 28 (2026 is not a leap year).
    recurringService()->generateDue(Carbon::parse('2026-01-31'));

    expect($rule->fresh()->next_run_date->toDateString())->toBe('2026-02-28');
});

test('generation stops at the end date', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account, [
        'frequency' => 'monthly',
        'start_date' => '2026-01-15',
        'end_date' => '2026-03-15',
    ]);

    // Jan, Feb, Mar are within the window; Apr is past the end date.
    $generated = recurringService()->generateDue(Carbon::parse('2026-12-31'));

    expect($generated)->toBe(3);
});

test('inactive rules are not generated', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account, ['start_date' => '2026-06-01']);
    recurringService()->cancel($rule);

    $generated = recurringService()->generateDue(Carbon::parse('2026-12-31'));

    expect($generated)->toBe(0);
});

// ---------------------------------------------------------------------------
// FR-3.4 — cancelling / editing never touches generated rows
// ---------------------------------------------------------------------------

test('cancelling a rule keeps already-generated transactions intact', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user, ['current_balance' => 1000]);
    $rule = makeRule($user, $account, ['amount' => 100, 'start_date' => '2026-06-01']);

    recurringService()->generateDue(Carbon::parse('2026-06-01'));
    expect(Transaction::where('recurring_transaction_id', $rule->id)->count())->toBe(1);
    expect((float) $account->fresh()->current_balance)->toBe(900.0);

    $this->actingAs($user)
        ->post("/budget/recurring/{$rule->id}/cancel")
        ->assertRedirect('/budget/recurring');

    expect($rule->fresh()->is_active)->toBeFalse();
    // Generated row and its balance effect are untouched.
    expect(Transaction::where('recurring_transaction_id', $rule->id)->count())->toBe(1);
    expect((float) $account->fresh()->current_balance)->toBe(900.0);
});

test('editing a rule leaves prior generated transactions unchanged', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user);
    $rule = makeRule($user, $account, ['amount' => 100, 'description' => 'Old name', 'start_date' => '2026-06-01']);

    recurringService()->generateDue(Carbon::parse('2026-06-01'));
    $generated = Transaction::where('recurring_transaction_id', $rule->id)->firstOrFail();

    $this->actingAs($user)
        ->put("/budget/recurring/{$rule->id}", [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 500,
            'frequency' => 'monthly',
            'start_date' => '2026-06-01',
            'description' => 'New name',
        ])
        ->assertRedirect('/budget/recurring');

    // The previously generated ledger row keeps its original amount/description.
    expect($generated->fresh()->amount)->toBe('100.00');
    expect($generated->fresh()->description)->toBe('Old name');
});

// ---------------------------------------------------------------------------
// Console command
// ---------------------------------------------------------------------------

test('kova:process-recurring command generates due transactions', function () {
    $user = User::factory()->create();
    $account = makeRecurringAccount($user, ['current_balance' => 1000]);
    $rule = makeRule($user, $account, [
        'amount' => 100,
        'frequency' => 'monthly',
        'start_date' => Carbon::now()->subDay()->toDateString(),
    ]);

    $this->artisan('kova:process-recurring')->assertSuccessful();

    expect(Transaction::where('recurring_transaction_id', $rule->id)->exists())->toBeTrue();
    expect((float) $account->fresh()->current_balance)->toBe(900.0);
});

// ---------------------------------------------------------------------------
// On-request catch-up middleware (scheduler-less environments)
// ---------------------------------------------------------------------------

test('an authenticated web request materializes the user\'s due occurrences', function () {
    Carbon::setTestNow('2026-06-10');

    $user = User::factory()->create();
    $account = makeRecurringAccount($user, ['current_balance' => 1000]);
    $rule = makeRule($user, $account, [
        'amount' => 100,
        'frequency' => 'monthly',
        'start_date' => '2026-06-01',
    ]);

    $this->actingAs($user)->get('/budget/recurring')->assertOk();

    // June 1 occurrence generated and the schedule advanced past the cutoff.
    expect(Transaction::where('recurring_transaction_id', $rule->id)->count())->toBe(1);
    expect((float) $account->fresh()->current_balance)->toBe(900.0);
    expect($rule->fresh()->next_run_date->toDateString())->toBe('2026-07-01');

    Carbon::setTestNow();
});

test('the middleware never touches another user\'s rules', function () {
    Carbon::setTestNow('2026-06-10');

    $owner = User::factory()->create();
    $ownerAccount = makeRecurringAccount($owner);
    $ownerRule = makeRule($owner, $ownerAccount, ['start_date' => '2026-06-01']);

    $visitor = User::factory()->create();
    makeRecurringAccount($visitor);

    // The visitor's request must not generate the owner's due occurrence.
    $this->actingAs($visitor)->get('/budget/recurring')->assertOk();

    expect(Transaction::where('recurring_transaction_id', $ownerRule->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});
