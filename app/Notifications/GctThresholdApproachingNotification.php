<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GctThresholdApproachingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly float $percentage,
        private readonly float $turnover,
        private readonly float $threshold,
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
        $pct = number_format($this->percentage, 0);

        return (new MailMessage())
            ->subject("GCT Threshold Alert — {$pct}% Reached")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your annual turnover has reached {$pct}% of the JMD " . number_format($this->threshold, 0) . " GCT registration threshold.")
            ->line('Current turnover: JMD ' . number_format($this->turnover, 2))
            ->action('View Tax Profile', url('/tax-profile'))
            ->line('If you exceed this threshold, GCT registration becomes mandatory.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'gct_threshold',
            'percentage' => $this->percentage,
            'turnover' => $this->turnover,
            'threshold' => $this->threshold,
            'message' => "Your turnover has reached " . number_format($this->percentage, 0) . "% of the GCT registration threshold.",
        ];
    }
}
