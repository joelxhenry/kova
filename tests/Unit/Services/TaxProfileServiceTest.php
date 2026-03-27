<?php

declare(strict_types=1);

use App\Models\TaxProfile;
use App\Models\User;
use App\Services\TaxProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it creates a new tax profile for a user', function () {
    $service = new TaxProfileService();
    $user = User::factory()->create();

    $profile = $service->upsert($user, [
        'trn' => '123456789',
        'business_type' => 'specified_services',
        'is_gct_registered' => false,
        'nis_rate' => 3.00,
        'education_tax_rate' => 2.25,
    ]);

    expect($profile)->toBeInstanceOf(TaxProfile::class)
        ->and($profile->user_id)->toBe($user->id)
        ->and($profile->trn)->toBe('123456789')
        ->and($profile->business_type)->toBe('specified_services');
});

test('it updates an existing tax profile instead of creating a duplicate', function () {
    $service = new TaxProfileService();
    $user = User::factory()->create();

    $service->upsert($user, [
        'business_type' => 'other',
        'is_gct_registered' => false,
        'nis_rate' => 3.00,
        'education_tax_rate' => 2.25,
    ]);

    $service->upsert($user, [
        'business_type' => 'construction',
        'is_gct_registered' => false,
        'nis_rate' => 3.00,
        'education_tax_rate' => 2.25,
    ]);

    expect(TaxProfile::where('user_id', $user->id)->count())->toBe(1)
        ->and($user->fresh()->taxProfile->business_type)->toBe('construction');
});

test('it clears gct_registration_date when not gct registered', function () {
    $service = new TaxProfileService();
    $user = User::factory()->create();

    $service->upsert($user, [
        'business_type' => 'specified_services',
        'is_gct_registered' => true,
        'gct_registration_date' => '2025-06-01',
        'nis_rate' => 3.00,
        'education_tax_rate' => 2.25,
    ]);

    expect($user->fresh()->taxProfile->gct_registration_date)->not->toBeNull();

    $service->upsert($user, [
        'business_type' => 'specified_services',
        'is_gct_registered' => false,
        'gct_registration_date' => '2025-06-01',
        'nis_rate' => 3.00,
        'education_tax_rate' => 2.25,
    ]);

    expect($user->fresh()->taxProfile->gct_registration_date)->toBeNull();
});
