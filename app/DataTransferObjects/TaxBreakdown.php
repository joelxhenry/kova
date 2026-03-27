<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class TaxBreakdown
{
    public function __construct(
        public readonly float $grossIncome,
        public readonly float $totalExpenses,
        public readonly float $netIncome,
        public readonly float $taxFreeAmount,
        public readonly float $bracket25Amount,
        public readonly float $bracket25Tax,
        public readonly float $bracket30Amount,
        public readonly float $bracket30Tax,
        public readonly float $totalIncomeTax,
        public readonly float $nisContribution,
        public readonly float $nhtContribution,
        public readonly float $educationTax,
        public readonly float $totalTaxLiability,
        public readonly float $withholdingCredits,
        public readonly float $netTaxPayable,
    ) {}

    /**
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            'grossIncome' => $this->grossIncome,
            'totalExpenses' => $this->totalExpenses,
            'netIncome' => $this->netIncome,
            'taxFreeAmount' => $this->taxFreeAmount,
            'bracket25Amount' => $this->bracket25Amount,
            'bracket25Tax' => $this->bracket25Tax,
            'bracket30Amount' => $this->bracket30Amount,
            'bracket30Tax' => $this->bracket30Tax,
            'totalIncomeTax' => $this->totalIncomeTax,
            'nisContribution' => $this->nisContribution,
            'nhtContribution' => $this->nhtContribution,
            'educationTax' => $this->educationTax,
            'totalTaxLiability' => $this->totalTaxLiability,
            'withholdingCredits' => $this->withholdingCredits,
            'netTaxPayable' => $this->netTaxPayable,
        ];
    }
}
