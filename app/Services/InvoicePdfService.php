<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function __construct(
        private readonly UserSettingService $userSettingService,
    ) {}

    /**
     * Generate a PDF for the given invoice.
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate(Invoice $invoice, User $user)
    {
        $invoice->load('items', 'client.contacts');

        $settings = $this->userSettingService->getOrCreate($user);
        $businessSettings = $settings->getGroup('business');

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'user' => $user,
            'businessSettings' => $businessSettings,
        ]);

        $pdf->setPaper('a4');

        return $pdf;
    }

    /**
     * Get the filename for the invoice PDF.
     */
    public function filename(Invoice $invoice): string
    {
        return $invoice->invoice_number . '.pdf';
    }
}
