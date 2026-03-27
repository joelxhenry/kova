<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\QuarterlyEstimate;
use App\DataTransferObjects\TaxBreakdown;
use App\Models\StatutoryRate;
use App\Models\User;
use Carbon\Carbon;

class TaxCalculationService
{
    public function calculateAnnualTax(User $user, int $year): TaxBreakdown
    {
        $grossIncome = $this->getGrossIncome($user, $year);
        $totalExpenses = $this->getTotalExpenses($user, $year);
        $netIncome = max(0.0, $grossIncome - $totalExpenses);

        $taxFreeThreshold = StatutoryRate::getValue('tax_free_threshold');
        $bracket25Limit = StatutoryRate::getValue('tax_bracket_25_limit');
        $nisRate = StatutoryRate::getValue('nis_rate') / 100;
        $nhtRate = StatutoryRate::getValue('nht_rate') / 100;
        $educationTaxRate = StatutoryRate::getValue('education_tax_rate') / 100;

        // Progressive tax calculation
        $taxFreeAmount = min($netIncome, $taxFreeThreshold);
        $taxableAfterThreshold = max(0.0, $netIncome - $taxFreeThreshold);

        $bracket25Cap = max(0.0, $bracket25Limit - $taxFreeThreshold);
        $bracket25Amount = min($taxableAfterThreshold, $bracket25Cap);
        $bracket25Tax = round($bracket25Amount * 0.25, 2);

        $bracket30Amount = max(0.0, $taxableAfterThreshold - $bracket25Cap);
        $bracket30Tax = round($bracket30Amount * 0.30, 2);

        $totalIncomeTax = $bracket25Tax + $bracket30Tax;

        // Statutory contributions on net income
        $nisContribution = round($netIncome * $nisRate, 2);
        $nhtContribution = round($netIncome * $nhtRate, 2);
        $educationTax = round($netIncome * $educationTaxRate, 2);

        $totalTaxLiability = $totalIncomeTax + $nisContribution + $nhtContribution + $educationTax;

        $withholdingCredits = $this->getWithholdingCredits($user, $year);

        $netTaxPayable = round($totalTaxLiability - $withholdingCredits, 2);

        return new TaxBreakdown(
            grossIncome: round($grossIncome, 2),
            totalExpenses: round($totalExpenses, 2),
            netIncome: round($netIncome, 2),
            taxFreeAmount: round($taxFreeAmount, 2),
            bracket25Amount: round($bracket25Amount, 2),
            bracket25Tax: $bracket25Tax,
            bracket30Amount: round($bracket30Amount, 2),
            bracket30Tax: $bracket30Tax,
            totalIncomeTax: round($totalIncomeTax, 2),
            nisContribution: $nisContribution,
            nhtContribution: $nhtContribution,
            educationTax: $educationTax,
            totalTaxLiability: round($totalTaxLiability, 2),
            withholdingCredits: round($withholdingCredits, 2),
            netTaxPayable: $netTaxPayable,
        );
    }

    /**
     * @return list<QuarterlyEstimate>
     */
    public function calculateQuarterlyEstimates(User $user, int $year): array
    {
        $breakdown = $this->calculateAnnualTax($user, $year);

        $quarterlyAmount = $breakdown->netTaxPayable > 0
            ? round($breakdown->netTaxPayable / 4, 2)
            : 0.0;

        $deadlines = [
            1 => "{$year}-03-15",
            2 => "{$year}-06-15",
            3 => "{$year}-09-15",
            4 => "{$year}-12-15",
        ];

        $today = Carbon::today();
        $estimates = [];

        foreach ($deadlines as $quarter => $deadline) {
            $estimates[] = new QuarterlyEstimate(
                quarter: $quarter,
                deadline: $deadline,
                amountDue: $quarterlyAmount,
                isPast: Carbon::parse($deadline)->lt($today),
            );
        }

        return $estimates;
    }

    private function getGrossIncome(User $user, int $year): float
    {
        $invoiceIncome = (float) $user->invoices()
            ->where('status', 'paid')
            ->whereYear('issue_date', $year)
            ->sum('subtotal');

        $otherIncome = (float) $user->incomeEntries()
            ->whereYear('date_received', $year)
            ->sum('amount');

        return $invoiceIncome + $otherIncome;
    }

    private function getTotalExpenses(User $user, int $year): float
    {
        return (float) $user->expenses()
            ->whereYear('date_incurred', $year)
            ->sum('amount');
    }

    private function getWithholdingCredits(User $user, int $year): float
    {
        $invoiceCredits = (float) $user->invoices()
            ->where('status', 'paid')
            ->whereYear('issue_date', $year)
            ->sum('withholding_tax_amount');

        $incomeCredits = (float) $user->incomeEntries()
            ->whereYear('date_received', $year)
            ->sum('withholding_tax_applied');

        $manualCredits = (float) $user->withholdingCredits()
            ->where('tax_year', $year)
            ->sum('amount');

        return $invoiceCredits + $incomeCredits + $manualCredits;
    }
}
