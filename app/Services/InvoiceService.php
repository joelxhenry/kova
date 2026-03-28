<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;

class InvoiceService
{
    public function __construct(
        private readonly UserSettingService $userSettingService,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @param list<array{description: string, quantity: float|string, unit_price: float|string}> $items
     */
    public function create(User $user, array $data, array $items): Invoice
    {
        $data['invoice_number'] = $this->userSettingService->generateInvoiceNumber($user);

        $subtotal = $this->calculateSubtotal($items);

        $invoice = $user->invoices()->create([
            ...$data,
            'subtotal' => $subtotal,
            'total' => $subtotal,
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
        $subtotal = $this->calculateSubtotal($items);

        $invoice->update([
            ...$data,
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);

        $this->syncItems($invoice, $items);

        return $invoice->fresh(['items', 'client']);
    }

    public function delete(Invoice $invoice): void
    {
        $invoice->delete();
    }

    public function updateStatus(Invoice $invoice, string $status): Invoice
    {
        $invoice->update(['status' => $status]);

        return $invoice->fresh(['items', 'client']);
    }

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
