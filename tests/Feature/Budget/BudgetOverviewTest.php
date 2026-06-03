<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access the budget overview', function () {
    $this->get('/budget')->assertRedirect('/login');
});

test('budget overview renders with the expected props', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['type' => 'debit', 'opening_balance' => 1000, 'current_balance' => 1000]);

    $this->actingAs($user)
        ->get('/budget')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Budget/Index')
            ->has('accounts', 1)
            ->has('summary')
            ->has('recentTransactions')
            ->has('projection.labels')
            ->has('projection.aggregate')
            ->where('summary.net_worth', 1000));
});

test('budget overview lists recent income/expense entries but excludes transfers', function () {
    $user = User::factory()->create();
    $checking = Account::factory()->for($user)->create(['name' => 'Checking']);
    $savings = Account::factory()->for($user)->create(['name' => 'Savings']);

    Transaction::create(['user_id' => $user->id, 'account_id' => $checking->id, 'type' => 'expense', 'amount' => 50, 'date' => '2026-05-01', 'description' => 'Groceries']);
    Transaction::create(['user_id' => $user->id, 'account_id' => $checking->id, 'type' => 'income', 'amount' => 500, 'date' => '2026-05-02', 'description' => 'Payment']);
    Transaction::create(['user_id' => $user->id, 'account_id' => $checking->id, 'transfer_account_id' => $savings->id, 'type' => 'transfer', 'amount' => 100, 'date' => '2026-05-03', 'description' => 'Transfer']);

    $this->actingAs($user)
        ->get('/budget')
        ->assertInertia(fn ($page) => $page->has('recentTransactions', 2));
});

test('budget overview only shows the current users accounts', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Account::factory()->for($owner)->create(['name' => 'Mine']);
    Account::factory()->for($other)->create(['name' => 'Theirs']);

    $this->actingAs($owner)
        ->get('/budget')
        ->assertInertia(fn ($page) => $page
            ->has('accounts', 1)
            ->where('accounts.0.name', 'Mine'));
});
