<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IncomeEntry;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TaxProfile;
use App\Models\User;
use App\Services\TajFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupTajUser(float $invoiceSubtotal = 0, float $expense = 0): User
{
    $user = User::factory()->create(['name' => 'Test Taxpayer']);
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services', 'trn' => '123456789']);

    if ($invoiceSubtotal > 0) {
        $client = Client::create(['user_id' => $user->id, 'name' => 'Client', 'is_designated_entity' => false]);
        $invoice = Invoice::create([
            'user_id' => $user->id, 'client_id' => $client->id,
            'invoice_number' => 'INV-0001', 'issue_date' => '2025-06-01',
            'subtotal' => $invoiceSubtotal, 'total' => $invoiceSubtotal,
            'net_receivable' => $invoiceSubtotal, 'status' => 'paid',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'description' => 'Service',
            'quantity' => 1, 'unit_price' => $invoiceSubtotal, 'amount' => $invoiceSubtotal,
        ]);
    }

    if ($expense > 0) {
        $category = ExpenseCategory::whereNull('user_id')->first();
        Expense::create([
            'user_id' => $user->id, 'expense_category_id' => $category->id,
            'description' => 'Business expense', 'amount' => $expense, 'date_incurred' => '2025-06-01',
        ]);
    }

    return $user;
}

test('buildFormData returns correct structure', function () {
    $user = setupTajUser(invoiceSubtotal: 4000000, expense: 500000);
    $service = app(TajFormService::class);
    $data = $service->buildFormData($user, 2025);

    expect($data)->toHaveKeys(['taxpayer', 'tax_year', 'form_type', 'income', 'expenses', 'computation'])
        ->and($data['taxpayer']['name'])->toBe('Test Taxpayer')
        ->and($data['taxpayer']['trn'])->toBe('123456789')
        ->and($data['tax_year'])->toBe(2025)
        ->and($data['form_type'])->toBe('S04');
});

test('buildFormData income matches gross income', function () {
    $user = setupTajUser(invoiceSubtotal: 3000000);
    $service = app(TajFormService::class);
    $data = $service->buildFormData($user, 2025);

    expect($data['income']['gross_professional_income'])->toBe(3000000.0);
});

test('buildFormData expenses broken down by category', function () {
    $user = User::factory()->create();
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'other']);

    $cat1 = ExpenseCategory::where('name', 'Equipment')->first();
    $cat2 = ExpenseCategory::where('name', 'Fuel & Transport')->first();

    Expense::create(['user_id' => $user->id, 'expense_category_id' => $cat1->id, 'description' => 'Laptop', 'amount' => 150000, 'date_incurred' => '2025-03-01']);
    Expense::create(['user_id' => $user->id, 'expense_category_id' => $cat1->id, 'description' => 'Monitor', 'amount' => 50000, 'date_incurred' => '2025-04-01']);
    Expense::create(['user_id' => $user->id, 'expense_category_id' => $cat2->id, 'description' => 'Gas', 'amount' => 30000, 'date_incurred' => '2025-05-01']);

    $service = app(TajFormService::class);
    $data = $service->buildFormData($user, 2025);

    expect($data['expenses']['by_category']['Equipment'])->toBe(200000.0)
        ->and($data['expenses']['by_category']['Fuel & Transport'])->toBe(30000.0)
        ->and($data['expenses']['total'])->toBe(230000.0);
});

test('buildFormData computation matches tax breakdown', function () {
    $user = setupTajUser(invoiceSubtotal: 8000000, expense: 1000000);
    $service = app(TajFormService::class);
    $data = $service->buildFormData($user, 2025);
    $comp = $data['computation'];

    expect($comp['net_statutory_income'])->toBe(7000000.0)
        ->and($comp['total_income_tax'])->toBeGreaterThan(0)
        ->and($comp['nis_contribution'])->toBeGreaterThan(0)
        ->and($comp['nht_contribution'])->toBeGreaterThan(0)
        ->and($comp['education_tax'])->toBeGreaterThan(0)
        ->and($comp['total_tax_liability'])->toBe(
            $comp['total_income_tax'] + $comp['nis_contribution'] + $comp['nht_contribution'] + $comp['education_tax']
        );
});

test('generateSnapshot creates a record', function () {
    $user = setupTajUser(invoiceSubtotal: 2000000);
    $service = app(TajFormService::class);

    $snapshot = $service->generateSnapshot($user, 2025);

    expect($snapshot->user_id)->toBe($user->id)
        ->and($snapshot->tax_year)->toBe(2025)
        ->and($snapshot->form_type)->toBe('S04')
        ->and($snapshot->data)->toBeArray()
        ->and($snapshot->data['taxpayer']['name'])->toBe('Test Taxpayer')
        ->and($snapshot->generated_at)->not->toBeNull();
});

test('multiple snapshots are preserved for audit', function () {
    $user = setupTajUser(invoiceSubtotal: 2000000);
    $service = app(TajFormService::class);

    $service->generateSnapshot($user, 2025);
    $service->generateSnapshot($user, 2025);
    $service->generateSnapshot($user, 2025);

    expect($user->taxFormSnapshots()->where('tax_year', 2025)->count())->toBe(3);
});
