<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\StatutoryRate;
use App\Models\User;

class InvoiceService
{
    public function __construct(
        private readonly WithholdingCreditService $withholdingCreditService,
        private readonly UserSettingService $userSettingService,
    ) {}
    /**
     * @param array<string, mixed> $data
     * @param list<array{description: string, quantity: float|string, unit_price: float|string}> $items
     */
    public function create(User $user, array $data, array $items): Invoice
    {
        $data['invoice_number'] = $this->userSettingService->generateInvoiceNumber($user);

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

        $invoice = $invoice->fresh(['items', 'client']);

        // Auto-create withholding credit when invoice is marked as paid
        if ($invoice->status === 'paid') {
            $this->withholdingCreditService->createFromInvoice($invoice);
        }

        return $invoice;
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->delete();
    }

    /**
     * Update only the status of an invoice.
     */
    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);

        if ($status === 'paid') {
            $this->withholdingCreditService->createFromInvoice($invoice);
        }

        return $invoice->fresh(['items', 'client']);
    }

    /**
     * Duplicate an invoice as a new draft.
     */
    public function duplicate(Invoice $invoice): Invoice
    {
        $user = $invoice->user;
        $items = $invoice->items->map(fn ($item) => [
            'description' => $item->description,
            'unit' => $item->unit,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
        ])->toArray();

        return $this->create($user, [
            'client_id' => $invoice->client_id,
            'issue_date' => now()->toDateString(),
            'due_date' => $invoice->due_date ? now()->addDays($invoice->issue_date->diffInDays($invoice->due_date))->toDateString() : null,
            'status' => 'draft',
            'notes' => $invoice->notes,
        ], $items);
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
                'unit' => $item['unit'] ?? null,
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'amount' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
                'sort_order' => $index,
            ]);
        }
    }
}
