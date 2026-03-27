<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSetting;
use App\Services\UserSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('getOrCreate creates settings if none exist', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);

    expect($user->settings)->toBeNull();

    $setting = $service->getOrCreate($user);

    expect($setting)->toBeInstanceOf(UserSetting::class)
        ->and($setting->user_id)->toBe($user->id);
});

test('getOrCreate returns existing settings', function () {
    $user = User::factory()->create();
    UserSetting::create(['user_id' => $user->id, 'settings' => ['business_name' => 'Test Co']]);

    $service = app(UserSettingService::class);
    $setting = $service->getOrCreate($user);

    expect($setting->get('business_name'))->toBe('Test Co');
    expect(UserSetting::where('user_id', $user->id)->count())->toBe(1);
});

test('get returns default when key not set', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);
    $setting = $service->getOrCreate($user);

    expect($setting->get('invoice_prefix'))->toBe('INV')
        ->and($setting->get('invoice_padding'))->toBe(4)
        ->and($setting->get('business_country'))->toBe('Jamaica')
        ->and($setting->get('nonexistent', 'fallback'))->toBe('fallback');
});

test('getGroup returns merged defaults', function () {
    $user = User::factory()->create();
    UserSetting::create(['user_id' => $user->id, 'settings' => ['invoice_prefix' => 'KV']]);

    $service = app(UserSettingService::class);
    $setting = $service->getOrCreate($user);
    $group = $setting->getGroup('invoicing');

    expect($group['invoice_prefix'])->toBe('KV')
        ->and($group['invoice_separator'])->toBe('-')
        ->and($group['invoice_next_number'])->toBe(1)
        ->and($group['invoice_padding'])->toBe(4);
});

test('updateGroup updates only keys in that group', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);

    $service->updateGroup($user, 'business', [
        'business_name' => 'Kova Ltd',
        'business_city' => 'Kingston',
    ]);

    $setting = $user->fresh()->settings;
    expect($setting->get('business_name'))->toBe('Kova Ltd')
        ->and($setting->get('business_city'))->toBe('Kingston');
});

test('updateGroup ignores keys not in group defaults', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);

    $service->updateGroup($user, 'business', [
        'business_name' => 'Test',
        'invoice_prefix' => 'HACK', // not in business group
    ]);

    $setting = $user->fresh()->settings;
    expect($setting->get('business_name'))->toBe('Test')
        ->and($setting->get('invoice_prefix'))->toBe('INV'); // unchanged
});

test('generateInvoiceNumber uses settings and increments', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);

    $service->updateGroup($user, 'invoicing', [
        'invoice_prefix' => 'KV',
        'invoice_separator' => '/',
        'invoice_next_number' => 42,
        'invoice_padding' => 3,
    ]);

    $number = $service->generateInvoiceNumber($user);
    expect($number)->toBe('KV/042');

    // Next call should increment
    $number2 = $service->generateInvoiceNumber($user);
    expect($number2)->toBe('KV/043');
});

test('generateInvoiceNumber uses defaults when no settings', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);

    $number = $service->generateInvoiceNumber($user);
    expect($number)->toBe('INV-0001');
});

test('previewInvoiceNumber does not increment', function () {
    $user = User::factory()->create();
    $service = app(UserSettingService::class);

    $preview1 = $service->previewInvoiceNumber($user);
    $preview2 = $service->previewInvoiceNumber($user);

    expect($preview1)->toBe('INV-0001')
        ->and($preview2)->toBe('INV-0001');
});
