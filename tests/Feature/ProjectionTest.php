<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
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
function makeFeatureAccount(User $user, array $attributes = []): Account
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

test('guests cannot access projections', function () {
    $this->get('/budget/projections')->assertRedirect('/login');
});

test('projections page renders with components and props', function () {
    $user = User::factory()->create();
    makeFeatureAccount($user);

    $this->actingAs($user)
        ->get('/budget/projections')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Projections')
            ->has('projection.labels')
            ->has('projection.datasets')
            ->has('projection.aggregate')
            ->has('projection.alerts')
            ->has('accounts', 1)
            ->where('filters.timeframe', '30d'));
});

test('each timeframe returns the correct horizon length (FR-4.4)', function (string $timeframe) {
    $user = User::factory()->create();
    makeFeatureAccount($user);

    $today = Carbon::today();
    $until = match ($timeframe) {
        '3m' => $today->copy()->addMonthsNoOverflow(3),
        '6m' => $today->copy()->addMonthsNoOverflow(6),
        '1y' => $today->copy()->addYearNoOverflow(),
        default => $today->copy()->addDays(30),
    };
    $expectedCount = (int) $today->diffInDays($until) + 1;

    $this->actingAs($user)
        ->get("/budget/projections?timeframe={$timeframe}")
        ->assertInertia(fn ($page) => $page
            ->where('filters.timeframe', $timeframe)
            ->has('projection.labels', $expectedCount)
            ->where('projection.labels.0', $today->toDateString()));
})->with(['30d', '3m', '6m', '1y']);

test('an invalid timeframe falls back to 30 days', function () {
    $user = User::factory()->create();
    makeFeatureAccount($user);

    $this->actingAs($user)
        ->get('/budget/projections?timeframe=bogus')
        ->assertInertia(fn ($page) => $page->where('filters.timeframe', '30d'));
});

test('account_ids filter restricts the projected datasets (FR-4.3)', function () {
    $user = User::factory()->create();
    $a = makeFeatureAccount($user, ['name' => 'A']);
    makeFeatureAccount($user, ['name' => 'B']);

    $this->actingAs($user)
        ->get("/budget/projections?account_ids[]={$a->id}")
        ->assertInertia(fn ($page) => $page
            ->has('projection.datasets', 1)
            ->where('projection.datasets.0.name', 'A'));
});

test('another users accounts are never projected', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerAccount = makeFeatureAccount($owner, ['name' => 'Owner Account']);

    // The intruder passes the owner's account id; ownership filtering drops it.
    $this->actingAs($intruder)
        ->get("/budget/projections?account_ids[]={$ownerAccount->id}")
        ->assertInertia(fn ($page) => $page->has('projection.datasets', 0));
});

test('a below-zero debit projection surfaces an alert in the page props (FR-4.5)', function () {
    $user = User::factory()->create();
    $account = makeFeatureAccount($user, ['current_balance' => 100]);
    $user->recurringTransactions()->create([
        'account_id' => $account->id,
        'type' => 'expense',
        'amount' => 200,
        'frequency' => 'monthly',
        'start_date' => '2026-06-10',
        'next_run_date' => '2026-06-10',
        'description' => 'Big bill',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/budget/projections?timeframe=3m')
        ->assertInertia(fn ($page) => $page
            ->has('projection.alerts', 1)
            ->where('projection.alerts.0.date', '2026-06-10'));
});
