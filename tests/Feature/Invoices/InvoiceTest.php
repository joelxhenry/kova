<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithClientAndProfile(array $profileOverrides = [], array $clientOverrides = []): array
{
    $user = User::factory()->create();
    TaxProfile::create([
        'user_id' => $user->id,
        'business_type' => 'specified_services',
        ...$profileOverrides,
    ]);
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Test Client',
        'is_designated_entity' => false,
        ...$clientOverrides,
    ]);

    return [$user, $client];
}

test('guests cannot access invoices', function () {
    $this->get('/invoices')->assertRedirect('/login');
});

test('user can view invoices index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/invoices')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Invoices/Index'));
});

test('user can create an invoice', function () {
    [$user, $client] = createUserWithClientAndProfile();

    $this->actingAs($user)
        ->post('/invoices', [
            'client_id' => $client->id,
            'issue_date' => '2025-06-01',
            'items' => [
                ['description' => 'Consulting', 'quantity' => 10, 'unit_price' => 5000],
            ],
        ])
        ->assertRedirect('/invoices');

    $invoice = $user->invoices()->first();
    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_number)->toBe('INV-0001')
        ->and((float) $invoice->subtotal)->toBe(50000.00)
        ->and((float) $invoice->total)->toBe(50000.00);
});

test('invoice auto-increments number', function () {
    [$user, $client] = createUserWithClientAndProfile();

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-01-01',
        'items' => [['description' => 'A', 'quantity' => 1, 'unit_price' => 100]],
    ]);
    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-02-01',
        'items' => [['description' => 'B', 'quantity' => 1, 'unit_price' => 200]],
    ]);

    $numbers = $user->invoices()->pluck('invoice_number')->toArray();
    expect($numbers)->toContain('INV-0001', 'INV-0002');
});

test('invoice calculates GCT when user is registered', function () {
    [$user, $client] = createUserWithClientAndProfile(['is_gct_registered' => true, 'gct_registration_date' => '2024-01-01']);

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-06-01',
        'items' => [['description' => 'Service', 'quantity' => 1, 'unit_price' => 100000]],
    ]);

    $invoice = $user->invoices()->first();
    expect((float) $invoice->gct_amount)->toBe(15000.00)
        ->and((float) $invoice->total)->toBe(115000.00);
});

test('invoice calculates withholding tax for designated entity', function () {
    [$user, $client] = createUserWithClientAndProfile(
        ['business_type' => 'specified_services'],
        ['is_designated_entity' => true],
    );

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-06-01',
        'items' => [['description' => 'IT Consulting', 'quantity' => 1, 'unit_price' => 100000]],
    ]);

    $invoice = $user->invoices()->first();
    // 3% of 100000 = 3000
    expect((float) $invoice->withholding_tax_amount)->toBe(3000.00)
        ->and((float) $invoice->net_receivable)->toBe(97000.00);
});

test('no withholding tax for invoices below threshold', function () {
    [$user, $client] = createUserWithClientAndProfile(
        ['business_type' => 'specified_services'],
        ['is_designated_entity' => true],
    );

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-06-01',
        'items' => [['description' => 'Small job', 'quantity' => 1, 'unit_price' => 40000]],
    ]);

    $invoice = $user->invoices()->first();
    expect((float) $invoice->withholding_tax_amount)->toBe(0.00);
});

test('invoice calculates contractors levy for construction', function () {
    [$user, $client] = createUserWithClientAndProfile(['business_type' => 'construction']);

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-06-01',
        'items' => [['description' => 'Foundation work', 'quantity' => 1, 'unit_price' => 200000]],
    ]);

    $invoice = $user->invoices()->first();
    // 2% of 200000 = 4000
    expect((float) $invoice->contractors_levy_amount)->toBe(4000.00)
        ->and((float) $invoice->withholding_tax_amount)->toBe(0.00);
});

test('items are required', function () {
    [$user, $client] = createUserWithClientAndProfile();

    $this->actingAs($user)
        ->post('/invoices', [
            'client_id' => $client->id,
            'issue_date' => '2025-06-01',
            'items' => [],
        ])
        ->assertSessionHasErrors('items');
});

test('user cannot view another users invoice', function () {
    $user = User::factory()->create();
    [$other, $client] = createUserWithClientAndProfile();

    $invoice = Invoice::create([
        'user_id' => $other->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-01-01',
        'subtotal' => 1000, 'total' => 1000, 'net_receivable' => 1000,
    ]);

    $this->actingAs($user)->get("/invoices/{$invoice->id}")->assertStatus(403);
});

test('user can delete an invoice', function () {
    [$user, $client] = createUserWithClientAndProfile();

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id, 'issue_date' => '2025-06-01',
        'items' => [['description' => 'A', 'quantity' => 1, 'unit_price' => 100]],
    ]);

    $invoice = $user->invoices()->first();

    $this->actingAs($user)->delete("/invoices/{$invoice->id}")->assertRedirect('/invoices');
    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
});

test('client_id must belong to authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::create(['user_id' => $other->id, 'name' => 'Other', 'is_designated_entity' => false]);

    $this->actingAs($user)
        ->post('/invoices', [
            'client_id' => $client->id,
            'issue_date' => '2025-06-01',
            'items' => [['description' => 'A', 'quantity' => 1, 'unit_price' => 100]],
        ])
        ->assertSessionHasErrors('client_id');
});
