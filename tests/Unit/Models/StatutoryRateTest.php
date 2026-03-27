<?php

declare(strict_types=1);

use App\Models\StatutoryRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('statutory rates are seeded with defaults', function () {
    expect(StatutoryRate::count())->toBeGreaterThanOrEqual(9);
    expect(StatutoryRate::where('key', 'nis_rate')->exists())->toBeTrue();
    expect(StatutoryRate::where('key', 'education_tax_rate')->exists())->toBeTrue();
    expect(StatutoryRate::where('key', 'gct_rate')->exists())->toBeTrue();
    expect(StatutoryRate::where('key', 'tax_free_threshold')->exists())->toBeTrue();
});

test('getValue returns correct rate', function () {
    expect(StatutoryRate::getValue('nis_rate'))->toBe(3.0);
    expect(StatutoryRate::getValue('education_tax_rate'))->toBe(2.25);
    expect(StatutoryRate::getValue('tax_free_threshold'))->toBe(1700088.0);
});

test('getValue returns zero for non-existent key', function () {
    expect(StatutoryRate::getValue('nonexistent_key'))->toBe(0.0);
});
