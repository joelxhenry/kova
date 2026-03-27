<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\StatutoryRate;
use App\Models\User;

class InvoiceService
{
    /**
     * @param array<string, mixed> $data
     * @param list<array{description: string, quantity: float|string, unit_price: float|string}> $items
     */
    public function create(User $user, array $data, array $items): Invoice
    {
        $data['invoice_number'] = $this->generateInvoiceNumber($user);

        $client = Client::findOrFail($data['client_id']);
        $taxProfile = $user->taxProfile;

        $subtotal = $this->calculateSubtotal($items);
        $gctAmount = $this->calculateGct($user, $subtotal);
        $total = $subtotal + $gctAmount;
        $withholdingTax = $this->calculateWithholdingTax($client, $subtotal, $taxProfile?->business_type);
        $contractorsLevy = $this->calculateContractorsLevy($subtotal, $taxProfile?->business_type);
        $netReceivable = $total - $withholdingTax - $contractorsLevy;

        $invoice = $user->invoices()->create([
            ...$data,
            'subtotal' => $subtotal,
            'gct_amount' => $gctAmount,
            'total' => $total,
            'withholding_tax_amount' => $withholdingTax,
            'contractors_levy_amount' => $contractorsLevy,
            'net_receivable' => $netReceivable,
        ]);

        $this->syncItems($invoice, $items);

        return $invoice->load('items', 'client');
    }

    /**
     * @param array<string, mixed> $data
     * @param list<array{description: string, quantity: float|string, unit_price: float|string}> $items
     */
    public function update(Invoice $invoice, array $data, array $items): Invoice
    {
        $user = $invoice->user;
        $client = Client::findOrFail($data['client_id'] ?? $invoice->client_id);
        $taxProfile = $user->taxProfile;

        $subtotal = $this->calculateSubtotal($items);
        $gctAmount = $this->calculateGct($user, $subtotal);
        $total = $subtotal + $gctAmount;
        $withholdingTax = $this->calculateWithholdingTax($client, $subtotal, $taxProfile?->business_type);
        $contractorsLevy = $this->calculateContractorsLevy($subtotal, $taxProfile?->business_type);
        $netReceivable = $total - $withholdingTax - $contractorsLevy;

        $invoice->update([
            ...$data,
            'subtotal' => $subtotal,
            'gct_amount' => $gctAmount,
            'total' => $total,
            'withholding_tax_amount' => $withholdingTax,
            'contractors_levy_amount' => $contractorsLevy,
            'net_receivable' => $netReceivable,
        ]);

        $this->syncItems($invoice, $items);

        return $invoice->fresh(['items', 'client']);
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->delete();
    }

    private function generateInvoiceNumber(User $user): string
    {
        $latest = $user->invoices()->max('invoice_number');

        if ($latest === null) {
            return 'INV-0001';
        }

        $number = (int) str_replace('INV-', '', $latest);

        return 'INV-' . str_pad((string) ($number + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param list<array{description: string, quantity: float|string, unit_price: float|string}> $items
     */
    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
        }

        return round($subtotal, 2);
    }

    private function calculateGct(User $user, float $subtotal): float
    {
        $taxProfile = $user->taxProfile;

        if (! $taxProfile?->is_gct_registered) {
            return 0.0;
        }

        $gctRate = StatutoryRate::getValue('gct_rate');

        return round($subtotal * $gctRate / 100, 2);
    }

    private function calculateWithholdingTax(Client $client, float $subtotal, ?string $businessType): float
    {
        if (! $client->is_designated_entity) {
            return 0.0;
        }

        $threshold = StatutoryRate::getValue('withholding_tax_invoice_threshold');

        if ($subtotal < $threshold) {
            return 0.0;
        }

        if ($businessType === null || in_array($businessType, ['construction', 'haulage', 'tillage'], true)) {
            return 0.0;
        }

        $rate = StatutoryRate::getValue('withholding_tax_rate');

        return round($subtotal * $rate / 100, 2);
    }

    private function calculateContractorsLevy(float $subtotal, ?string $businessType): float
    {
        if (! in_array($businessType, ['construction', 'haulage', 'tillage'], true)) {
            return 0.0;
        }

        $rate = StatutoryRate::getValue('contractors_levy_rate');

        return round($subtotal * $rate / 100, 2);
    }

    /**
     * @param list<array{description: string, quantity: float|string, unit_price: float|string}> $items
     */
    private function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach ($items as $index => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'amount' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                'sort_order' => $index,
            ]);
        }
    }
}
