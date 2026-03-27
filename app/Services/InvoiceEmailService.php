<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\InvoiceEmail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailService
{
    public function __construct(
        private readonly UserSettingService $userSettingService,
        private readonly InvoicePdfService $invoicePdfService,
    ) {}

    /**
     * Send an invoice email to the user-selected recipients with PDF attachment.
     *
     * @param list<string> $recipients
     */
    public function sendTo(Invoice $invoice, User $user, array $recipients): void
    {
        if (empty($recipients)) {
            return;
        }

        $settings = $this->userSettingService->getOrCreate($user);
        $emailSettings = $settings->getGroup('email');
        $businessSettings = $settings->getGroup('business');

        $subject = $this->interpolate($emailSettings['invoice_email_subject'], $invoice, $businessSettings);
        $greeting = $this->interpolate($emailSettings['invoice_email_greeting'], $invoice, $businessSettings);
        $body = $this->interpolate($emailSettings['invoice_email_body'], $invoice, $businessSettings);
        $footer = $emailSettings['invoice_email_footer'];
        $paymentInstructions = $emailSettings['invoice_email_include_payment_instructions']
            ? ($businessSettings['payment_instructions'] ?? '')
            : '';

        $pdf = $this->invoicePdfService->generate($invoice, $user);
        $filename = $this->invoicePdfService->filename($invoice);

        $mailable = new InvoiceEmail(
            invoice: $invoice,
            subject: $subject,
            greeting: $greeting,
            body: $body,
            footer: $footer,
            paymentInstructions: $paymentInstructions,
            businessName: $businessSettings['business_name'] ?? $user->name,
            pdfContent: $pdf->output(),
            pdfFilename: $filename,
        );

        Mail::to($recipients)->send($mailable);
    }

    /**
     * Get all available recipient emails for an invoice.
     *
     * @return list<array{email: string, label: string, type: string}>
     */
    public function getAvailableRecipients(Invoice $invoice): array
    {
        $recipients = [];

        if ($invoice->client?->email) {
            $recipients[] = [
                'email' => $invoice->client->email,
                'label' => $invoice->client->name,
                'type' => 'client',
            ];
        }

        if ($invoice->client?->contacts) {
            foreach ($invoice->client->contacts as $contact) {
                if ($contact->email) {
                    $alreadyAdded = array_filter($recipients, fn ($r) => $r['email'] === $contact->email);
                    if (empty($alreadyAdded)) {
                        $recipients[] = [
                            'email' => $contact->email,
                            'label' => "{$contact->first_name} {$contact->last_name}",
                            'type' => 'contact',
                        ];
                    }
                }
            }
        }

        return $recipients;
    }

    /**
     * @param array<string, mixed> $businessSettings
     */
    private function interpolate(string $template, Invoice $invoice, array $businessSettings): string
    {
        $total = number_format((float) $invoice->total, 2);

        return str_replace(
            ['{invoice_number}', '{business_name}', '{client_name}', '{total}'],
            [
                $invoice->invoice_number,
                $businessSettings['business_name'] ?? '',
                $invoice->client?->name ?? '',
                "J\${$total}",
            ],
            $template,
        );
    }
}
