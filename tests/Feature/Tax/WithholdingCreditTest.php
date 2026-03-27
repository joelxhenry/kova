<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\TaxProfile;
use App\Models\User;
use App\Models\WithholdingCredit;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access withholding credits', function () {
    $this->get('/withholding-credits')->assertRedirect('/login');
});

test('user can view withholding credits page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/withholding-credits')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Tax/WithholdingCredits')
            ->has('summary')
        );
});

test('user can create a manual withholding credit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/withholding-credits', [
            'amount' => 50000,
            'tax_year' => 2025,
            'date_withheld' => '2025-06-15',
            'description' => 'WHT from client payment',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('withholding_credits', [
        'user_id' => $user->id,
        'source_type' => 'manual',
        'amount' => 50000,
        'tax_year' => 2025,
    ]);
});

test('manual credit requires all fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/withholding-credits', [
            'amount' => '',
            'tax_year' => '',
            'date_withheld' => '',
            'description' => '',
        ])
        ->assertSessionHasErrors(['amount', 'tax_year', 'date_withheld', 'description']);
});

test('user can delete a manual withholding credit', function () {
    $user = User::factory()->create();
    $credit = WithholdingCredit::create([
        'user_id' => $user->id,
        'source_type' => 'manual',
        'amount' => 1000,
        'tax_year' => 2025,
        'date_withheld' => '2025-06-01',
        'description' => 'Test',
    ]);

    $this->actingAs($user)
        ->delete("/withholding-credits/{$credit->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('withholding_credits', ['id' => $credit->id]);
});

test('user cannot delete another users withholding credit', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $credit = WithholdingCredit::create([
        'user_id' => $other->id,
        'source_type' => 'manual',
        'amount' => 1000,
        'tax_year' => 2025,
        'date_withheld' => '2025-06-01',
        'description' => 'Test',
    ]);

    $this->actingAs($user)
        ->delete("/withholding-credits/{$credit->id}")
        ->assertStatus(403);
});

test('withholding credit auto-created when invoice marked paid', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services']);
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Gov Agency',
        'is_designated_entity' => true,
    ]);

    // Create invoice via service (it calculates withholding)
    $invoiceService = app(InvoiceService::class);
    $invoice = $invoiceService->create($user, [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
    ], [
        ['description' => 'IT Consulting', 'quantity' => 1, 'unit_price' => 100000],
    ]);

    expect((float) $invoice->withholding_tax_amount)->toBe(3000.0);

    // Now update to paid
    $invoiceService->update($invoice, [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
        'status' => 'paid',
    ], [
        ['description' => 'IT Consulting', 'quantity' => 1, 'unit_price' => 100000],
    ]);

    $this->assertDatabaseHas('withholding_credits', [
        'user_id' => $user->id,
        'source_type' => 'invoice',
        'source_id' => $invoice->id,
        'amount' => 3000,
    ]);
});

test('duplicate withholding credit not created on repeated paid update', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services']);
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Gov Agency',
        'is_designated_entity' => true,
    ]);

    $invoiceService = app(InvoiceService::class);
    $invoice = $invoiceService->create($user, [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
    ], [
        ['description' => 'Work', 'quantity' => 1, 'unit_price' => 100000],
    ]);

    $items = [['description' => 'Work', 'quantity' => 1, 'unit_price' => 100000]];

    // Mark paid twice
    $invoiceService->update($invoice, ['client_id' => $client->id, 'issue_date' => '2025-06-01', 'status' => 'paid'], $items);
    $invoiceService->update($invoice->fresh(), ['client_id' => $client->id, 'issue_date' => '2025-06-01', 'status' => 'paid'], $items);

    expect(WithholdingCredit::where('source_type', 'invoice')->where('source_id', $invoice->id)->count())->toBe(1);
});

test('summary includes all credit sources', function () {
    $user = User::factory()->create();
    $client = Client::create(['user_id' => $user->id, 'name' => 'C', 'is_designated_entity' => true]);

    // Paid invoice with withholding
    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
        'subtotal' => 100000, 'total' => 100000, 'net_receivable' => 97000,
        'withholding_tax_amount' => 3000, 'status' => 'paid',
    ]);

    // Manual credit
    WithholdingCredit::create([
        'user_id' => $user->id, 'source_type' => 'manual',
        'amount' => 5000, 'tax_year' => 2025,
        'date_withheld' => '2025-06-15', 'description' => 'Manual WHT',
    ]);

    $this->actingAs($user)
        ->get('/withholding-credits?year=2025')
        ->assertInertia(fn ($page) => $page
            ->where('summary.invoiceCredits', 3000)
            ->where('summary.manualCredits', 5000)
            ->where('summary.totalCredits', 8000)
        );
});
