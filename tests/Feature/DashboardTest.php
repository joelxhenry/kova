<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('dashboard renders with required props', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('recentInvoices')
            ->has('recentExpenses')
        );
});

test('dashboard shows recent invoices', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Client']);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2026-01-01',
        'subtotal' => 100000, 'total' => 100000, 'status' => 'paid',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('recentInvoices', 1)
        );
});

test('dashboard budget summary is null when the user has no accounts', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('budgetSummary', null));
});

test('dashboard surfaces net worth and cash on hand when accounts exist', function () {
    $user = User::factory()->create();
    Account::factory()->for($user)->create(['type' => 'debit', 'opening_balance' => 1000, 'current_balance' => 1000]);
    Account::factory()->for($user)->credit()->create(['opening_balance' => 300, 'current_balance' => 300]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('budgetSummary.debit_total', 1000)
            ->where('budgetSummary.credit_total', 300)
            ->where('budgetSummary.net_worth', 700));
});

test('reports page loads with filters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->has('summary')
            ->has('byStatus')
            ->has('byClient')
            ->has('byCategory')
            ->has('monthly')
            ->has('clients')
            ->has('filters')
        );
});

test('reports page accepts date filters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/reports?from=2026-01-01&to=2026-03-31')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('filters.from', '2026-01-01')
            ->where('filters.to', '2026-03-31')
        );
});

test('reports page accepts client filter', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'TestCo']);

    $this->actingAs($user)
        ->get("/reports?client_id={$client->id}")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('filters.client_id', (string) $client->id)
        );
});
