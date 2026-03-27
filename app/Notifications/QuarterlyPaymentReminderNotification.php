<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuarterlyPaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $quarter,
        private readonly string $deadline,
        private readonly float $amountDue,
        private readonly int $daysUntil,
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
            ->subject("Q{$this->quarter} Tax Payment Due in {$this->daysUntil} Days")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your Q{$this->quarter} estimated tax payment of JMD " . number_format($this->amountDue, 2) . " is due on {$this->deadline}.")
            ->line("You have {$this->daysUntil} days remaining.")
            ->action('View Dashboard', url('/dashboard'))
            ->line('Stay on top of your TAJ obligations with Kova.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quarterly_reminder',
            'quarter' => $this->quarter,
            'deadline' => $this->deadline,
            'amount_due' => $this->amountDue,
            'days_until' => $this->daysUntil,
            'message' => "Q{$this->quarter} payment of JMD " . number_format($this->amountDue, 2) . " due in {$this->daysUntil} days ({$this->deadline}).",
        ];
    }
}
