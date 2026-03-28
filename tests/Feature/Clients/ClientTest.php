<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access clients', function () {
    $this->get('/clients')->assertRedirect('/login');
});

test('user can view clients index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/clients')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Clients/Index'));
});

test('user can create a client with address and contacts', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/clients', [
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'phone' => '876-555-1234',
            'trn' => '123456789',
            'is_designated_entity' => true,
            'address_line_1' => '10 Hope Road',
            'city' => 'Kingston',
            'state_or_parish' => 'Kingston',
            'country' => 'Jamaica',
            'contacts' => [
                ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@acme.com', 'phone' => '876-555-0001'],
                ['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@acme.com'],
            ],
        ]);

    $client = $user->clients()->first();
    $response->assertRedirect("/clients/{$client->id}");

    $this->assertDatabaseHas('clients', [
        'user_id' => $user->id,
        'name' => 'Acme Corp',
        'address_line_1' => '10 Hope Road',
        'city' => 'Kingston',
    ]);

    expect($client->contacts)->toHaveCount(2);
    $this->assertDatabaseHas('client_contacts', ['client_id' => $client->id, 'first_name' => 'John', 'last_name' => 'Doe']);
    $this->assertDatabaseHas('client_contacts', ['client_id' => $client->id, 'first_name' => 'Jane', 'last_name' => 'Smith']);
});

test('client name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/clients', ['name' => '', 'is_designated_entity' => false])
        ->assertSessionHasErrors('name');
});

test('contact first and last name are required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/clients', [
            'name' => 'Test',
            'is_designated_entity' => false,
            'contacts' => [
                ['first_name' => '', 'last_name' => ''],
            ],
        ])
        ->assertSessionHasErrors(['contacts.0.first_name', 'contacts.0.last_name']);
});

test('user can view client show page', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Show Client', 'is_designated_entity' => false]);

    $this->actingAs($user)
        ->get("/clients/{$client->id}")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Clients/Show')
            ->has('client')
            ->has('invoices')
            ->has('summary')
        );
});

test('show page includes financial summary', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Summary Client', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
        'subtotal' => 100000, 'total' => 100000, 'net_receivable' => 100000,
        'status' => 'paid',
    ]);
    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0002', 'issue_date' => '2025-07-01',
        'subtotal' => 50000, 'total' => 50000, 'net_receivable' => 50000,
        'status' => 'sent',
    ]);

    $this->actingAs($user)
        ->get("/clients/{$client->id}")
        ->assertInertia(fn ($page) => $page
            ->where('summary.totalInvoiced', 150000)
            ->where('summary.balanceDue', 50000)
        );
});

test('show page includes contacts', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Contact Client', 'is_designated_entity' => false]);
    ClientContact::create(['client_id' => $client->id, 'first_name' => 'Alice', 'last_name' => 'Brown']);

    $this->actingAs($user)
        ->get("/clients/{$client->id}")
        ->assertInertia(fn ($page) => $page
            ->has('client.contacts', 1)
        );
});

test('user can update client with contacts sync', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Old', 'is_designated_entity' => false]);
    $existing = ClientContact::create(['client_id' => $client->id, 'first_name' => 'Keep', 'last_name' => 'Me']);
    ClientContact::create(['client_id' => $client->id, 'first_name' => 'Remove', 'last_name' => 'Me']);

    $this->actingAs($user)
        ->put("/clients/{$client->id}", [
            'name' => 'Updated',
            'is_designated_entity' => false,
            'contacts' => [
                ['id' => $existing->id, 'first_name' => 'Kept', 'last_name' => 'Updated'],
                ['first_name' => 'New', 'last_name' => 'Contact', 'email' => 'new@test.com'],
            ],
        ])
        ->assertRedirect("/clients/{$client->id}");

    expect($client->fresh()->name)->toBe('Updated');
    expect($client->fresh()->contacts)->toHaveCount(2);
    $this->assertDatabaseHas('client_contacts', ['id' => $existing->id, 'first_name' => 'Kept']);
    $this->assertDatabaseMissing('client_contacts', ['first_name' => 'Remove']);
    $this->assertDatabaseHas('client_contacts', ['first_name' => 'New']);
});

test('address fields persist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/clients', [
            'name' => 'Address Test',
            'is_designated_entity' => false,
            'address_line_1' => '42 Main St',
            'address_line_2' => 'Suite 100',
            'city' => 'Montego Bay',
            'state_or_parish' => 'St James',
            'postal_code' => 'JMAKN01',
            'country' => 'Jamaica',
        ]);

    $this->assertDatabaseHas('clients', [
        'address_line_1' => '42 Main St',
        'city' => 'Montego Bay',
        'state_or_parish' => 'St James',
        'postal_code' => 'JMAKN01',
    ]);
});

test('user cannot access another users client', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::create(['user_id' => $other->id, 'name' => 'Other', 'is_designated_entity' => false]);

    $this->actingAs($user)->get("/clients/{$client->id}")->assertStatus(403);
    $this->actingAs($user)->get("/clients/{$client->id}/edit")->assertStatus(403);
});

test('user can delete a client', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Delete Me', 'is_designated_entity' => false]);
    ClientContact::create(['client_id' => $client->id, 'first_name' => 'Gone', 'last_name' => 'Too']);

    $this->actingAs($user)->delete("/clients/{$client->id}")->assertRedirect('/clients');

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    $this->assertDatabaseMissing('client_contacts', ['client_id' => $client->id]);
});

test('creating client without contacts works', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/clients', ['name' => 'No Contacts', 'is_designated_entity' => false]);

    $client = $user->clients()->first();
    expect($client)->not->toBeNull()
        ->and($client->contacts)->toHaveCount(0);
});
