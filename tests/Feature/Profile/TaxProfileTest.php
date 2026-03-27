<?php

declare(strict_types=1);

use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access tax profile page', function () {
    $this->get('/tax-profile')
        ->assertRedirect('/login');
});

test('authenticated user can view tax profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/tax-profile')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Profile/TaxProfile'));
});

test('tax profile page includes statutory rates', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/tax-profile')
        ->assertInertia(fn ($page) => $page
            ->component('Profile/TaxProfile')
            ->has('statutoryRates.nis_rate')
            ->has('statutoryRates.education_tax_rate')
            ->has('statutoryRates.gct_rate')
        );
});

test('user can create a tax profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/tax-profile', [
            'trn' => '123456789',
            'business_type' => 'specified_services',
            'is_gct_registered' => false,
        ])
        ->assertRedirect('/tax-profile')
        ->assertSessionHas('status', 'Tax profile updated.');

    $this->assertDatabaseHas('tax_profiles', [
        'user_id' => $user->id,
        'trn' => '123456789',
        'business_type' => 'specified_services',
        'is_gct_registered' => false,
    ]);
});

test('user can update an existing tax profile', function () {
    $user = User::factory()->create();
    TaxProfile::create([
        'user_id' => $user->id,
        'business_type' => 'other',
    ]);

    $this->actingAs($user)
        ->put('/tax-profile', [
            'trn' => '987654321',
            'business_type' => 'construction',
            'is_gct_registered' => true,
            'gct_registration_date' => '2025-01-15',
        ])
        ->assertRedirect('/tax-profile');

    $this->assertDatabaseHas('tax_profiles', [
        'user_id' => $user->id,
        'trn' => '987654321',
        'business_type' => 'construction',
        'is_gct_registered' => true,
    ]);

    expect(TaxProfile::where('user_id', $user->id)->count())->toBe(1);
});

test('business_type is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/tax-profile', [
            'business_type' => '',
            'is_gct_registered' => false,
        ])
        ->assertSessionHasErrors('business_type');
});

test('business_type must be a valid enum value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/tax-profile', [
            'business_type' => 'invalid_type',
            'is_gct_registered' => false,
        ])
        ->assertSessionHasErrors('business_type');
});

test('trn must be exactly 9 digits', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/tax-profile', [
            'trn' => '12345',
            'business_type' => 'other',
            'is_gct_registered' => false,
        ])
        ->assertSessionHasErrors('trn');
});

test('gct_registration_date is required when gct registered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/tax-profile', [
            'business_type' => 'specified_services',
            'is_gct_registered' => true,
            'gct_registration_date' => '',
        ])
        ->assertSessionHasErrors('gct_registration_date');
});

test('gct_registration_date is cleared when not gct registered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/tax-profile', [
            'business_type' => 'specified_services',
            'is_gct_registered' => false,
            'gct_registration_date' => '2025-01-01',
        ])
        ->assertRedirect('/tax-profile');

    expect($user->fresh()->taxProfile->gct_registration_date)->toBeNull();
});

test('existing tax profile data is passed to the edit page', function () {
    $user = User::factory()->create();
    TaxProfile::create([
        'user_id' => $user->id,
        'trn' => '111222333',
        'business_type' => 'haulage',
    ]);

    $this->actingAs($user)
        ->get('/tax-profile')
        ->assertInertia(fn ($page) => $page
            ->component('Profile/TaxProfile')
            ->where('taxProfile.trn', '111222333')
            ->where('taxProfile.business_type', 'haulage')
        );
});

test('tax profile is shared in inertia props', function () {
    $user = User::factory()->create();
    TaxProfile::create([
        'user_id' => $user->id,
        'business_type' => 'construction',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.has_tax_profile', true)
            ->where('auth.taxProfile.business_type', 'construction')
        );
});

test('tax profile is null in props when not set', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.has_tax_profile', false)
            ->where('auth.taxProfile', null)
        );
});
