<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\IncomeEntry;
use App\Models\Invoice;
use App\Models\TaxProfile;
use App\Models\User;
use App\Services\GctMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('zero turnover returns zero percentage', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    $service = app(GctMonitorService::class);
    $status = $service->getStatus($user, 2025);

    expect($status['turnover'])->toBe(0.0)
        ->and($status['percentage'])->toBe(0.0)
        ->and($status['isRegistered'])->toBeFalse();
});

test('turnover includes sent and paid invoices', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);
    $client = Client::create(['user_id' => $user->id, 'name' => 'C', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
        'subtotal' => 5000000, 'total' => 5000000, 'net_receivable' => 5000000,
        'status' => 'paid',
    ]);
    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0002', 'issue_date' => '2025-07-01',
        'subtotal' => 3000000, 'total' => 3000000, 'net_receivable' => 3000000,
        'status' => 'sent',
    ]);
    // Draft should NOT count
    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0003', 'issue_date' => '2025-08-01',
        'subtotal' => 10000000, 'total' => 10000000, 'net_receivable' => 10000000,
        'status' => 'draft',
    ]);

    $service = app(GctMonitorService::class);
    $status = $service->getStatus($user, 2025);

    expect($status['turnover'])->toBe(8000000.0);
});

test('turnover includes income entries', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    IncomeEntry::create([
        'user_id' => $user->id, 'source' => 'Cash',
        'amount' => 2000000, 'date_received' => '2025-03-15',
    ]);

    $service = app(GctMonitorService::class);
    $status = $service->getStatus($user, 2025);

    expect($status['turnover'])->toBe(2000000.0);
});

test('percentage caps at 100', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);
    $client = Client::create(['user_id' => $user->id, 'name' => 'C', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
        'subtotal' => 20000000, 'total' => 20000000, 'net_receivable' => 20000000,
        'status' => 'paid',
    ]);

    $service = app(GctMonitorService::class);
    $status = $service->getStatus($user, 2025);

    expect($status['percentage'])->toBe(100.0);
});

test('isRegistered reflects tax profile', function () {
    $user = User::factory()->create();
    TaxProfile::create([
        'user_id' => $user->id,
        'business_type' => 'other',
        'is_gct_registered' => true,
        'gct_registration_date' => '2024-01-01',
    ]);

    $service = app(GctMonitorService::class);
    $status = $service->getStatus($user, 2025);

    expect($status['isRegistered'])->toBeTrue();
});
