<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StatutoryRate;
use App\Models\User;

class GctMonitorService
{
    /**
     * @return array{turnover: float, threshold: float, percentage: float, isRegistered: bool}
     */
    public function getStatus(User $user, int $year): array
    {
        $turnover = $this->getAnnualTurnover($user, $year);
        $threshold = StatutoryRate::getValue('gct_registration_threshold');
        $percentage = $threshold > 0 ? round(($turnover / $threshold) * 100, 1) : 0.0;
        $isRegistered = (bool) $user->taxProfile?->is_gct_registered;

        return [
            'turnover' => round($turnover, 2),
            'threshold' => $threshold,
            'percentage' => min($percentage, 100.0),
            'isRegistered' => $isRegistered,
        ];
    }

    private function getAnnualTurnover(User $user, int $year): float
    {
        return (float) $user->invoices()
            ->whereIn('status', ['sent', 'paid'])
            ->whereYear('issue_date', $year)
            ->sum('subtotal');
    }
}
