<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('dashboard renders with all required props', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('year')
            ->has('taxBreakdown')
            ->has('quarterlyEstimates')
            ->has('gctStatus')
            ->has('monthlyData')
        );
});

test('dashboard defaults to current year', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('year', (int) date('Y'))
        );
});

test('dashboard accepts year parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard?year=2024')
        ->assertInertia(fn ($page) => $page
            ->where('year', 2024)
        );
});

test('dashboard tax breakdown reflects real data', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services']);
    $client = Client::create(['user_id' => $user->id, 'name' => 'Client', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
        'subtotal' => 3000000, 'total' => 3000000, 'net_receivable' => 3000000,
        'status' => 'paid',
    ]);

    $this->actingAs($user)
        ->get('/dashboard?year=2025')
        ->assertInertia(fn ($page) => $page
            ->where('taxBreakdown.grossIncome', 3000000)
            ->where('taxBreakdown.netIncome', 3000000)
        );
});

test('dashboard monthly data has 12 months', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('monthlyData', 12)
        );
});

test('dashboard gct status reflects turnover', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);
    $client = Client::create(['user_id' => $user->id, 'name' => 'Client', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-15',
        'subtotal' => 7500000, 'total' => 7500000, 'net_receivable' => 7500000,
        'status' => 'paid',
    ]);

    $this->actingAs($user)
        ->get('/dashboard?year=2025')
        ->assertInertia(fn ($page) => $page
            ->where('gctStatus.turnover', 7500000)
            ->where('gctStatus.percentage', 50)
        );
});

test('dashboard quarterly estimates has 4 quarters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('quarterlyEstimates', 4)
        );
});
