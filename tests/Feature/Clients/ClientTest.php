<?php

declare(strict_types=1);

use App\Models\Client;
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

test('user can create a client', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/clients', [
            'name' => 'Acme Corp',
            'email' => 'billing@acme.com',
            'phone' => '876-555-1234',
            'trn' => '123456789',
            'is_designated_entity' => true,
        ])
        ->assertRedirect('/clients');

    $this->assertDatabaseHas('clients', [
        'user_id' => $user->id,
        'name' => 'Acme Corp',
        'is_designated_entity' => true,
    ]);
});

test('client name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/clients', [
            'name' => '',
            'is_designated_entity' => false,
        ])
        ->assertSessionHasErrors('name');
});

test('user can update a client', function () {
    $user = User::factory()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'is_designated_entity' => false,
    ]);

    $this->actingAs($user)
        ->put("/clients/{$client->id}", [
            'name' => 'New Name',
            'is_designated_entity' => true,
        ])
        ->assertRedirect('/clients');

    expect($client->fresh()->name)->toBe('New Name')
        ->and($client->fresh()->is_designated_entity)->toBeTrue();
});

test('user cannot access another users client', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $client = Client::create([
        'user_id' => $other->id,
        'name' => 'Other Client',
        'is_designated_entity' => false,
    ]);

    $this->actingAs($user)
        ->get("/clients/{$client->id}/edit")
        ->assertStatus(403);
});

test('user can delete a client', function () {
    $user = User::factory()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Delete Me',
        'is_designated_entity' => false,
    ]);

    $this->actingAs($user)
        ->delete("/clients/{$client->id}")
        ->assertRedirect('/clients');

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
});
