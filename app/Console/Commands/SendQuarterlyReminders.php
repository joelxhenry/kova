<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\QuarterlyPaymentReminderNotification;
use App\Services\TaxCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendQuarterlyReminders extends Command
{
    protected $signature = 'kova:send-quarterly-reminders';
    protected $description = 'Send reminders for upcoming quarterly tax payment deadlines';

    public function handle(TaxCalculationService $taxService): int
    {
        $year = (int) date('Y');
        $today = Carbon::today();
        $reminderDays = [14, 3];

        $deadlines = [
            1 => Carbon::create($year, 3, 15),
            2 => Carbon::create($year, 6, 15),
            3 => Carbon::create($year, 9, 15),
            4 => Carbon::create($year, 12, 15),
        ];

        $sent = 0;

        foreach ($deadlines as $quarter => $deadline) {
            $daysUntil = $today->diffInDays($deadline, false);

            if (! in_array((int) $daysUntil, $reminderDays, true)) {
                continue;
            }

            User::chunk(100, function ($users) use ($taxService, $year, $quarter, $deadline, $daysUntil, &$sent): void {
                foreach ($users as $user) {
                    $alreadySent = $user->notifications()
                        ->where('type', QuarterlyPaymentReminderNotification::class)
                        ->whereJsonContains('data->quarter', $quarter)
                        ->whereJsonContains('data->days_until', (int) $daysUntil)
                        ->whereYear('created_at', $year)
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $estimates = $taxService->calculateQuarterlyEstimates($user, $year);
                    $estimate = $estimates[$quarter - 1] ?? null;

                    if (! $estimate || $estimate->amountDue <= 0) {
                        continue;
                    }

                    $user->notify(new QuarterlyPaymentReminderNotification(
                        quarter: $quarter,
                        deadline: $deadline->toDateString(),
                        amountDue: $estimate->amountDue,
                        daysUntil: (int) $daysUntil,
                    ));

                    $sent++;
                }
            });
        }

        $this->info("Sent {$sent} quarterly reminders.");

        return self::SUCCESS;
    }
}
