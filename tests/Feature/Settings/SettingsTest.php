<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access settings', function () {
    $this->get('/settings')->assertRedirect('/login');
});

test('user can view settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('business')
            ->has('invoicing')
            ->has('email')
            ->has('invoiceNumberPreview')
        );
});

test('settings page returns defaults when no settings exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertInertia(fn ($page) => $page
            ->where('invoicing.invoice_prefix', 'INV')
            ->where('invoicing.invoice_padding', 4)
            ->where('business.business_country', 'Jamaica')
        );
});

test('user can update business settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/business', [
            'business_name' => 'Kova Solutions',
            'business_city' => 'Kingston',
            'business_country' => 'Jamaica',
            'business_phone' => '876-555-0000',
            'business_email' => 'info@kova.jm',
            'payment_terms' => 'Net 30',
        ])
        ->assertRedirect('/settings');

    $setting = $user->fresh()->settings;
    expect($setting->get('business_name'))->toBe('Kova Solutions')
        ->and($setting->get('business_city'))->toBe('Kingston');
});

test('user can update invoice settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/invoicing', [
            'invoice_prefix' => 'KV',
            'invoice_separator' => '/',
            'invoice_next_number' => 100,
            'invoice_padding' => 5,
        ])
        ->assertRedirect('/settings');

    $setting = $user->fresh()->settings;
    expect($setting->get('invoice_prefix'))->toBe('KV');
});

test('invoice settings validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/invoicing', [
            'invoice_prefix' => '',
            'invoice_separator' => '',
            'invoice_next_number' => 0,
            'invoice_padding' => 0,
        ])
        ->assertSessionHasErrors(['invoice_prefix', 'invoice_separator', 'invoice_next_number', 'invoice_padding']);
});

test('user can update email settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/email', [
            'invoice_email_subject' => 'Invoice {invoice_number}',
            'invoice_email_greeting' => 'Dear {client_name},',
            'invoice_email_body' => 'Attached is invoice {invoice_number} for {total}.',
            'invoice_email_footer' => 'Regards, {business_name}',
            'invoice_email_include_payment_instructions' => false,
        ])
        ->assertRedirect('/settings');

    $setting = $user->fresh()->settings;
    expect($setting->get('invoice_email_subject'))->toBe('Invoice {invoice_number}');
});

test('settings shared in inertia props', function () {
    $user = User::factory()->create();
    UserSetting::create(['user_id' => $user->id, 'settings' => ['business_name' => 'Shared Co']]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('settings.business_name', 'Shared Co')
        );
});

test('invoice number preview shown on settings page', function () {
    $user = User::factory()->create();
    UserSetting::create(['user_id' => $user->id, 'settings' => [
        'invoice_prefix' => 'KV',
        'invoice_separator' => '-',
        'invoice_next_number' => 7,
        'invoice_padding' => 3,
    ]]);

    $this->actingAs($user)
        ->get('/settings')
        ->assertInertia(fn ($page) => $page
            ->where('invoiceNumberPreview', 'KV-007')
        );
});
