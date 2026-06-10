<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RecurringTransactionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Materializes any due recurring transactions for the authenticated user on
 * request, standing in for the scheduler in environments without cron. Self-
 * throttling: generating an occurrence advances next_run_date, so the steady
 * state is a single indexed query that finds nothing due. A short-lived lock
 * stops concurrent requests from double-generating the same occurrence.
 */
class ApplyDueRecurringTransactions
{
    public function __construct(
        private readonly RecurringTransactionService $service,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            // Non-blocking: if another request already holds the lock it is
            // catching this user up, so we skip rather than queue behind it.
            $lock = Cache::lock("recurring:generate:{$user->id}", 10);

            if ($lock->get()) {
                try {
                    $this->service->generateDue(null, $user);
                } finally {
                    $lock->release();
                }
            }
        }

        return $next($request);
    }
}
