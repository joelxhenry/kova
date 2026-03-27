<?php

declare(strict_types=1);

use App\Models\TaxFormSnapshot;
use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access tax form', function () {
    $this->get('/tax-form')->assertRedirect('/login');
});

test('user can view tax form preview', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    $this->actingAs($user)
        ->get('/tax-form')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Tax/FormPreview')
            ->has('formData')
            ->has('year')
            ->has('snapshots')
        );
});

test('tax form accepts year parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/tax-form?year=2024')
        ->assertInertia(fn ($page) => $page->where('year', 2024));
});

test('tax form data includes all required sections', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services', 'trn' => '999888777']);

    $this->actingAs($user)
        ->get('/tax-form?year=2025')
        ->assertInertia(fn ($page) => $page
            ->has('formData.taxpayer')
            ->has('formData.income')
            ->has('formData.computation')
            ->where('formData.taxpayer.trn', '999888777')
        );
});

test('user can generate a snapshot', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    $this->actingAs($user)
        ->post('/tax-form/generate', ['year' => 2025])
        ->assertRedirect();

    $this->assertDatabaseHas('tax_form_snapshots', [
        'user_id' => $user->id,
        'tax_year' => 2025,
        'form_type' => 'S04',
    ]);
});

test('user can view a saved snapshot', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    $snapshot = TaxFormSnapshot::create([
        'user_id' => $user->id,
        'tax_year' => 2025,
        'form_type' => 'S04',
        'data' => ['taxpayer' => ['name' => 'Test'], 'computation' => []],
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/tax-form/snapshot/{$snapshot->id}")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Tax/FormPreview')
            ->where('viewingSnapshot', $snapshot->id)
        );
});

test('user cannot view another users snapshot', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $snapshot = TaxFormSnapshot::create([
        'user_id' => $other->id,
        'tax_year' => 2025,
        'form_type' => 'S04',
        'data' => ['taxpayer' => ['name' => 'Other']],
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get("/tax-form/snapshot/{$snapshot->id}")
        ->assertStatus(403);
});

test('snapshots list shows on preview page', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    TaxFormSnapshot::create([
        'user_id' => $user->id, 'tax_year' => 2025, 'form_type' => 'S04',
        'data' => [], 'generated_at' => now(),
    ]);
    TaxFormSnapshot::create([
        'user_id' => $user->id, 'tax_year' => 2025, 'form_type' => 'S04',
        'data' => [], 'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/tax-form?year=2025')
        ->assertInertia(fn ($page) => $page->has('snapshots', 2));
});
