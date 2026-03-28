<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithClient(): array
{
    $user = User::factory()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Test Client',
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
    [$user, $client] = createUserWithClient();

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
    [$user, $client] = createUserWithClient();

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

test('items are required', function () {
    [$user, $client] = createUserWithClient();

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
    [$other, $client] = createUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $other->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-01-01',
        'subtotal' => 1000, 'total' => 1000,
    ]);

    $this->actingAs($user)->get("/invoices/{$invoice->id}")->assertStatus(403);
});

test('user can delete an invoice', function () {
    [$user, $client] = createUserWithClient();

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
    $client = Client::create(['user_id' => $other->id, 'name' => 'Other']);

    $this->actingAs($user)
        ->post('/invoices', [
            'client_id' => $client->id,
            'issue_date' => '2025-06-01',
            'items' => [['description' => 'A', 'quantity' => 1, 'unit_price' => 100]],
        ])
        ->assertSessionHasErrors('client_id');
});
