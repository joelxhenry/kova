<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ProjectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectionController extends Controller
{
    public function __construct(
        private readonly ProjectionService $projectionService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $timeframe = $request->input('timeframe', '30d');
        if (! in_array($timeframe, ['30d', '3m', '6m', '1y'], true)) {
            $timeframe = '30d';
        }

        $until = $this->horizon($timeframe);

        // account_ids[] are coerced to ints; ownership is enforced inside the
        // service (only the user's own accounts are ever projected).
        $accountIds = array_values(array_filter(array_map(
            static fn ($id): int => (int) $id,
            (array) $request->input('account_ids', []),
        )));

        return Inertia::render('Budget/Projections', [
            'projection' => $this->projectionService->project($user, $until, $accountIds),
            'accounts' => $user->accounts()->active()->orderBy('name')->get(['id', 'name', 'type']),
            'filters' => [
                'timeframe' => $timeframe,
                'account_ids' => $accountIds,
            ],
        ]);
    }

    /**
     * Map a timeframe token to its end date relative to today (FR-4.4).
     */
    private function horizon(string $timeframe): Carbon
    {
        $today = Carbon::today();

        return match ($timeframe) {
            '3m' => $today->copy()->addMonthsNoOverflow(3),
            '6m' => $today->copy()->addMonthsNoOverflow(6),
            '1y' => $today->copy()->addYearNoOverflow(),
            default => $today->copy()->addDays(30),
        };
    }
}
