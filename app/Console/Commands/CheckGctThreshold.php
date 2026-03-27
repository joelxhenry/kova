<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\GctThresholdApproachingNotification;
use App\Services\GctMonitorService;
use Illuminate\Console\Command;

class CheckGctThreshold extends Command
{
    protected $signature = 'kova:check-gct-threshold';
    protected $description = 'Check users approaching GCT registration threshold and notify';

    public function handle(GctMonitorService $gctService): int
    {
        $year = (int) date('Y');
        $alertLevels = [80, 90, 100];
        $sent = 0;

        User::whereHas('taxProfile', fn ($q) => $q->where('is_gct_registered', false))
            ->chunk(100, function ($users) use ($gctService, $year, $alertLevels, &$sent): void {
                foreach ($users as $user) {
                    $status = $gctService->getStatus($user, $year);

                    foreach ($alertLevels as $level) {
                        if ($status['percentage'] < $level) {
                            continue;
                        }

                        $alreadySent = $user->notifications()
                            ->where('type', GctThresholdApproachingNotification::class)
                            ->whereJsonContains('data->percentage', $level)
                            ->whereYear('created_at', $year)
                            ->exists();

                        if ($alreadySent) {
                            continue;
                        }

                        $user->notify(new GctThresholdApproachingNotification(
                            percentage: (float) $level,
                            turnover: $status['turnover'],
                            threshold: $status['threshold'],
                        ));

                        $sent++;
                    }
                }
            });

        $this->info("Sent {$sent} GCT threshold alerts.");

        return self::SUCCESS;
    }
}
