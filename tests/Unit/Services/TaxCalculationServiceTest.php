<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TaxProfile;
use App\Models\User;
use App\Services\TaxCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupUserWithIncome(float $invoiceSubtotal = 0, float $withholdingOnInvoice = 0): User
{
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services']);

    if ($invoiceSubtotal > 0) {
        $client = Client::create(['user_id' => $user->id, 'name' => 'Client', 'is_designated_entity' => false]);
        $invoice = Invoice::create([
            'user_id' => $user->id, 'client_id' => $client->id,
            'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
            'subtotal' => $invoiceSubtotal, 'gct_amount' => 0,
            'total' => $invoiceSubtotal,
            'withholding_tax_amount' => $withholdingOnInvoice,
            'contractors_levy_amount' => 0,
            'net_receivable' => $invoiceSubtotal - $withholdingOnInvoice,
            'status' => 'paid',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'description' => 'Service',
            'quantity' => 1, 'unit_price' => $invoiceSubtotal, 'amount' => $invoiceSubtotal,
        ]);
    }

    return $user;
}

test('zero income produces zero tax', function () {
    $user = setupUserWithIncome();
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->grossIncome)->toBe(0.0)
        ->and($result->netIncome)->toBe(0.0)
        ->and($result->totalIncomeTax)->toBe(0.0)
        ->and($result->totalTaxLiability)->toBe(0.0)
        ->and($result->netTaxPayable)->toBe(0.0);
});

test('income below tax-free threshold pays no income tax', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 1500000);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->grossIncome)->toBe(1500000.0)
        ->and($result->netIncome)->toBe(1500000.0)
        ->and($result->taxFreeAmount)->toBe(1500000.0)
        ->and($result->bracket25Amount)->toBe(0.0)
        ->and($result->totalIncomeTax)->toBe(0.0);

    // NIS: 1500000 * 0.03 = 45000
    // NHT: 1500000 * 0.02 = 30000
    // Education: 1500000 * 0.0225 = 33750
    expect($result->nisContribution)->toBe(45000.0)
        ->and($result->nhtContribution)->toBe(30000.0)
        ->and($result->educationTax)->toBe(33750.0)
        ->and($result->totalTaxLiability)->toBe(108750.0);
});

test('income in 25% bracket calculates correctly', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 4000000);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->grossIncome)->toBe(4000000.0)
        ->and($result->taxFreeAmount)->toBe(1700088.0)
        ->and($result->bracket25Amount)->toBe(2299912.0)
        ->and($result->bracket25Tax)->toBe(574978.0)
        ->and($result->bracket30Amount)->toBe(0.0)
        ->and($result->bracket30Tax)->toBe(0.0);
});

test('income in 30% bracket calculates correctly', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 8000000);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->taxFreeAmount)->toBe(1700088.0)
        ->and($result->bracket25Amount)->toBe(4299912.0)
        ->and($result->bracket25Tax)->toBe(1074978.0)
        ->and($result->bracket30Amount)->toBe(2000000.0)
        ->and($result->bracket30Tax)->toBe(600000.0)
        ->and($result->totalIncomeTax)->toBe(1674978.0);
});

test('income exactly at tax-free threshold', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 1700088);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->taxFreeAmount)->toBe(1700088.0)
        ->and($result->bracket25Amount)->toBe(0.0)
        ->and($result->totalIncomeTax)->toBe(0.0);
});

test('income exactly at 25% bracket limit', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 6000000);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->bracket25Amount)->toBe(4299912.0)
        ->and($result->bracket30Amount)->toBe(0.0)
        ->and($result->bracket30Tax)->toBe(0.0);
});

test('withholding credits reduce net tax payable', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 4000000, withholdingOnInvoice: 120000);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->withholdingCredits)->toBe(120000.0)
        ->and($result->netTaxPayable)->toBe($result->totalTaxLiability - 120000.0);
});

test('withholding credits exceeding liability produce negative net payable (refund)', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 1000000, withholdingOnInvoice: 500000);
    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->netTaxPayable)->toBeLessThan(0);
});

test('only paid invoices count toward gross income', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);
    $client = Client::create(['user_id' => $user->id, 'name' => 'C', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
        'subtotal' => 1000000, 'total' => 1000000, 'net_receivable' => 1000000,
        'status' => 'paid',
    ]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0002', 'issue_date' => '2025-06-15',
        'subtotal' => 5000000, 'total' => 5000000, 'net_receivable' => 5000000,
        'status' => 'draft',
    ]);

    $service = app(TaxCalculationService::class);
    $result = $service->calculateAnnualTax($user, 2025);

    expect($result->grossIncome)->toBe(1000000.0);
});

test('quarterly estimates split evenly across 4 deadlines', function () {
    $user = setupUserWithIncome(invoiceSubtotal: 8000000);
    $service = app(TaxCalculationService::class);
    $estimates = $service->calculateQuarterlyEstimates($user, 2025);

    expect($estimates)->toHaveCount(4)
        ->and($estimates[0]->quarter)->toBe(1)
        ->and($estimates[0]->deadline)->toBe('2025-03-15')
        ->and($estimates[1]->deadline)->toBe('2025-06-15')
        ->and($estimates[2]->deadline)->toBe('2025-09-15')
        ->and($estimates[3]->deadline)->toBe('2025-12-15');

    $amount = $estimates[0]->amountDue;
    expect($amount)->toBeGreaterThan(0);
    foreach ($estimates as $e) {
        expect($e->amountDue)->toBe($amount);
    }
});

test('quarterly estimates are zero when no tax payable', function () {
    $user = setupUserWithIncome();
    $service = app(TaxCalculationService::class);
    $estimates = $service->calculateQuarterlyEstimates($user, 2025);

    foreach ($estimates as $e) {
        expect($e->amountDue)->toBe(0.0);
    }
});
