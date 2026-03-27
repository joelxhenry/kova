<?php

declare(strict_types=1);

use App\Models\StatutoryRate;
use App\Models\StatutoryRateAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('regular user cannot access statutory rates', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin/statutory-rates')->assertStatus(403);
});

test('admin can view statutory rates index', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/statutory-rates')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/StatutoryRates/Index')
            ->has('rates')
        );
});

test('index groups rates by key with current value', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/statutory-rates')
        ->assertInertia(fn ($page) => $page
            ->where('rates.0.key', 'contractors_levy_rate')
            ->has('rates.0.current_value')
            ->has('rates.0.version_count')
        );
});

test('admin can view rate detail page with versions', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/statutory-rates/gct_rate')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/StatutoryRates/Show')
            ->where('rateKey', 'gct_rate')
            ->has('versions')
            ->has('auditLogs')
        );
});

test('admin can add a new rate version', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post('/admin/statutory-rates/gct_rate', [
            'value' => 16.5,
            'effective_from' => '2026-04-01',
        ])
        ->assertRedirect('/admin/statutory-rates/gct_rate');

    $versions = StatutoryRate::where('key', 'gct_rate')
        ->orderByDesc('effective_from')
        ->get();

    expect($versions)->toHaveCount(2)
        ->and((float) $versions->first()->value)->toBe(16.5)
        ->and($versions->first()->effective_from->toDateString())->toBe('2026-04-01');
});

test('new version creates an audit log entry', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $oldRate = StatutoryRate::where('key', 'nis_rate')->first();

    $this->actingAs($admin)
        ->post('/admin/statutory-rates/nis_rate', [
            'value' => 3.5,
            'effective_from' => '2026-07-01',
        ]);

    $newRate = StatutoryRate::where('key', 'nis_rate')
        ->where('effective_from', '2026-07-01')
        ->first();

    $log = StatutoryRateAuditLog::where('statutory_rate_id', $newRate->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id)
        ->and((float) $log->old_value)->toBe((float) $oldRate->value)
        ->and((float) $log->new_value)->toBe(3.5);
});

test('duplicate effective_from for same key is rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $existing = StatutoryRate::where('key', 'gct_rate')->first();

    $this->actingAs($admin)
        ->post('/admin/statutory-rates/gct_rate', [
            'value' => 20,
            'effective_from' => $existing->effective_from->toDateString(),
        ])
        ->assertSessionHasErrors('effective_from');
});

test('validates required fields', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post('/admin/statutory-rates/gct_rate', [
            'value' => '',
            'effective_from' => '',
        ])
        ->assertSessionHasErrors(['value', 'effective_from']);
});

test('rejects negative values', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post('/admin/statutory-rates/gct_rate', [
            'value' => -5,
            'effective_from' => '2026-04-01',
        ])
        ->assertSessionHasErrors('value');
});

test('getValue returns rate effective at a given date', function () {
    // Original rate effective from 2024-01-01 is 15%
    StatutoryRate::create([
        'key' => 'gct_rate',
        'label' => 'GCT Rate (%)',
        'value' => 20,
        'description' => 'Updated GCT',
        'effective_from' => '2026-07-01',
    ]);

    // Before the new version — should get the original 15%
    expect(StatutoryRate::getValue('gct_rate', '2026-06-30'))->toBe(15.0);

    // After the new version — should get 20%
    expect(StatutoryRate::getValue('gct_rate', '2026-07-01'))->toBe(20.0);
    expect(StatutoryRate::getValue('gct_rate', '2027-01-01'))->toBe(20.0);
});

test('getValue with no date returns current rate', function () {
    expect(StatutoryRate::getValue('gct_rate'))->toBe(15.0);
});

test('old rates are preserved for historical calculations', function () {
    // Add a future rate
    StatutoryRate::create([
        'key' => 'nis_rate',
        'label' => 'NIS Rate (%)',
        'value' => 4.0,
        'description' => 'Updated NIS',
        'effective_from' => '2027-01-01',
    ]);

    // 2025 calculation should use the original 3% rate
    expect(StatutoryRate::getValue('nis_rate', '2025-06-01'))->toBe(3.0);

    // 2027 calculation should use the new 4% rate
    expect(StatutoryRate::getValue('nis_rate', '2027-06-01'))->toBe(4.0);
});

test('show page returns 404 for invalid key', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/statutory-rates/nonexistent_key')
        ->assertStatus(404);
});
