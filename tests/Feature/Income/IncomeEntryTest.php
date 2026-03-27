<?php

declare(strict_types=1);

use App\Models\IncomeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access income entries', function () {
    $this->get('/income')->assertRedirect('/login');
});

test('user can view income index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/income')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Income/Index'));
});

test('user can create an income entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/income', [
            'source' => 'Cash job',
            'description' => 'Fixed a server',
            'amount' => 25000.00,
            'date_received' => '2025-06-15',
            'withholding_tax_applied' => 750.00,
        ])
        ->assertRedirect('/income');

    $this->assertDatabaseHas('income_entries', [
        'user_id' => $user->id,
        'source' => 'Cash job',
        'amount' => 25000.00,
        'withholding_tax_applied' => 750.00,
    ]);
});

test('source and amount are required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/income', [
            'source' => '',
            'amount' => '',
            'date_received' => '2025-06-15',
        ])
        ->assertSessionHasErrors(['source', 'amount']);
});

test('user can update an income entry', function () {
    $user = User::factory()->create();
    $entry = IncomeEntry::create([
        'user_id' => $user->id,
        'source' => 'Old Source',
        'amount' => 1000,
        'date_received' => '2025-01-01',
    ]);

    $this->actingAs($user)
        ->put("/income/{$entry->id}", [
            'source' => 'New Source',
            'amount' => 2000,
            'date_received' => '2025-02-01',
        ])
        ->assertRedirect('/income');

    expect($entry->fresh()->source)->toBe('New Source')
        ->and((float) $entry->fresh()->amount)->toBe(2000.00);
});

test('user cannot access another users income entry', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $entry = IncomeEntry::create([
        'user_id' => $other->id,
        'source' => 'Other',
        'amount' => 1000,
        'date_received' => '2025-01-01',
    ]);

    $this->actingAs($user)->get("/income/{$entry->id}/edit")->assertStatus(403);
});

test('user can delete an income entry', function () {
    $user = User::factory()->create();
    $entry = IncomeEntry::create([
        'user_id' => $user->id,
        'source' => 'Delete me',
        'amount' => 1000,
        'date_received' => '2025-01-01',
    ]);

    $this->actingAs($user)->delete("/income/{$entry->id}")->assertRedirect('/income');
    $this->assertDatabaseMissing('income_entries', ['id' => $entry->id]);
});
