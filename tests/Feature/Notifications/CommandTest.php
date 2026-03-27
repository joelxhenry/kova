<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\IncomeEntry;
use App\Models\Invoice;
use App\Models\TaxProfile;
use App\Models\User;
use App\Notifications\GctThresholdApproachingNotification;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('check-overdue-invoices marks sent invoices past due date as overdue', function () {
    Notification::fake();

    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Client', 'is_designated_entity' => false]);

    $overdue = Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-01-01',
        'due_date' => '2025-01-31', 'subtotal' => 100000,
        'total' => 100000, 'net_receivable' => 100000, 'status' => 'sent',
    ]);

    // This one should NOT be marked overdue (no due date)
    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0002', 'issue_date' => '2025-01-01',
        'subtotal' => 50000, 'total' => 50000, 'net_receivable' => 50000,
        'status' => 'sent',
    ]);

    // This one should NOT be marked overdue (already paid)
    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0003', 'issue_date' => '2025-01-01',
        'due_date' => '2025-01-15', 'subtotal' => 75000,
        'total' => 75000, 'net_receivable' => 75000, 'status' => 'paid',
    ]);

    $this->artisan('kova:check-overdue-invoices')
        ->assertSuccessful();

    expect($overdue->fresh()->status)->toBe('overdue');

    Notification::assertSentTo($user, InvoiceOverdueNotification::class);
    Notification::assertCount(1);
});

test('check-gct-threshold sends alerts at correct levels', function () {
    Notification::fake();

    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other', 'is_gct_registered' => false]);

    // 85% of 15M = 12.75M
    IncomeEntry::create([
        'user_id' => $user->id, 'source' => 'Big contract',
        'amount' => 12750000, 'date_received' => now()->format('Y-m-d'),
    ]);

    $this->artisan('kova:check-gct-threshold')
        ->assertSuccessful();

    // Should fire 80% alert (85 >= 80)
    Notification::assertSentTo($user, GctThresholdApproachingNotification::class);
});

test('check-gct-threshold skips already registered users', function () {
    Notification::fake();

    $user = User::factory()->create();
    TaxProfile::create([
        'user_id' => $user->id, 'business_type' => 'other',
        'is_gct_registered' => true, 'gct_registration_date' => '2024-01-01',
    ]);

    IncomeEntry::create([
        'user_id' => $user->id, 'source' => 'Revenue',
        'amount' => 20000000, 'date_received' => now()->format('Y-m-d'),
    ]);

    $this->artisan('kova:check-gct-threshold')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('check-overdue-invoices does not re-notify already overdue invoices', function () {
    Notification::fake();

    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'Client', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-01-01',
        'due_date' => '2025-01-31', 'subtotal' => 100000,
        'total' => 100000, 'net_receivable' => 100000, 'status' => 'overdue',
    ]);

    $this->artisan('kova:check-overdue-invoices')
        ->assertSuccessful();

    Notification::assertNothingSent();
});
