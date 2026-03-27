<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TaxFormSnapshot;
use App\Models\User;

class TajFormService
{
    public function __construct(
        private readonly TaxCalculationService $taxCalculationService,
    ) {}

    /**
     * Build the S04/IT01 form data for a given tax year.
     *
     * @return array<string, mixed>
     */
    public function buildFormData(User $user, int $year): array
    {
        $breakdown = $this->taxCalculationService->calculateAnnualTax($user, $year);
        $taxProfile = $user->taxProfile;

        return [
            'taxpayer' => [
                'name' => $user->name,
                'trn' => $taxProfile?->trn,
                'business_type' => $taxProfile?->business_type,
                'is_gct_registered' => $taxProfile?->is_gct_registered ?? false,
            ],
            'tax_year' => $year,
            'form_type' => 'S04',
            'income' => [
                'gross_professional_income' => $breakdown->grossIncome,
            ],
            'computation' => [
                'net_statutory_income' => $breakdown->netIncome,
                'tax_free_threshold' => $breakdown->taxFreeAmount,
                'taxable_25_bracket' => $breakdown->bracket25Amount,
                'tax_on_25_bracket' => $breakdown->bracket25Tax,
                'taxable_30_bracket' => $breakdown->bracket30Amount,
                'tax_on_30_bracket' => $breakdown->bracket30Tax,
                'total_income_tax' => $breakdown->totalIncomeTax,
                'nis_contribution' => $breakdown->nisContribution,
                'nht_contribution' => $breakdown->nhtContribution,
                'education_tax' => $breakdown->educationTax,
                'total_tax_liability' => $breakdown->totalTaxLiability,
                'withholding_credits' => $breakdown->withholdingCredits,
                'net_tax_payable' => $breakdown->netTaxPayable,
            ],
        ];
    }

    /**
     * Generate (or regenerate) a snapshot for a given tax year.
     */
    public function generateSnapshot(User $user, int $year): TaxFormSnapshot
    {
        $formData = $this->buildFormData($user, $year);

        return TaxFormSnapshot::create([
            'user_id' => $user->id,
            'tax_year' => $year,
            'form_type' => 'S04',
            'data' => $formData,
            'generated_at' => now(),
        ]);
    }
}
