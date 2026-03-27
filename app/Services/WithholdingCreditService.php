<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Models\WithholdingCredit;

class WithholdingCreditService
{
    /**
     * @param array<string, mixed> $data
     */
    public function createManual(User $user, array $data): WithholdingCredit
    {
        return $user->withholdingCredits()->create([
            ...$data,
            'source_type' => 'manual',
        ]);
    }

    /**
     * Auto-create a withholding credit entry when an invoice is marked as paid.
     */
    public function createFromInvoice(Invoice $invoice): ?WithholdingCredit
    {
        if ((float) $invoice->withholding_tax_amount <= 0) {
            return null;
        }

        // Prevent duplicates
        $existing = WithholdingCredit::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->where('user_id', $invoice->user_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return WithholdingCredit::create([
            'user_id' => $invoice->user_id,
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'amount' => $invoice->withholding_tax_amount,
            'tax_year' => $invoice->issue_date->year,
            'date_withheld' => $invoice->issue_date,
            'description' => "WHT from {$invoice->invoice_number}",
        ]);
    }

    public function delete(WithholdingCredit $credit): void
    {
        $credit->delete();
    }
}
