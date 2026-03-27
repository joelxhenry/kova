<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly Invoice $invoice;
    public readonly string $emailGreeting;
    public readonly string $emailBody;
    public readonly string $emailFooter;
    public readonly string $paymentInstructions;
    public readonly string $businessName;

    private string $emailSubject;
    private ?string $pdfContent;
    private ?string $pdfFilename;

    public function __construct(
        Invoice $invoice,
        string $subject,
        string $greeting,
        string $body,
        string $footer,
        string $paymentInstructions,
        string $businessName,
        ?string $pdfContent = null,
        ?string $pdfFilename = null,
    ) {
        $this->invoice = $invoice;
        $this->emailSubject = $subject;
        $this->emailGreeting = $greeting;
        $this->emailBody = $body;
        $this->emailFooter = $footer;
        $this->paymentInstructions = $paymentInstructions;
        $this->businessName = $businessName;
        $this->pdfContent = $pdfContent;
        $this->pdfFilename = $pdfFilename;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'greeting' => $this->emailGreeting,
                'body' => $this->emailBody,
                'footer' => $this->emailFooter,
                'paymentInstructions' => $this->paymentInstructions,
                'businessName' => $this->businessName,
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        if ($this->pdfContent === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename ?? 'invoice.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
