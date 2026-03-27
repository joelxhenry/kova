<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Invoice {$this->invoice->invoice_number} is Overdue")
            ->greeting("Hi {$notifiable->name},")
            ->line("Invoice {$this->invoice->invoice_number} for JMD " . number_format((float) $this->invoice->total, 2) . " was due on {$this->invoice->due_date->format('M d, Y')} and is now overdue.")
            ->action('View Invoice', url("/invoices/{$this->invoice->id}"))
            ->line('Follow up with your client to collect payment.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_overdue',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total' => $this->invoice->total,
            'due_date' => $this->invoice->due_date->toDateString(),
            'message' => "Invoice {$this->invoice->invoice_number} (JMD " . number_format((float) $this->invoice->total, 2) . ") is overdue.",
        ];
    }
}
