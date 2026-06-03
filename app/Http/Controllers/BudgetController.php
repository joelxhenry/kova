<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AccountService;
use App\Services\ProjectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
        private readonly ProjectionService $projectionService,
    ) {}

    /**
     * Budgeting overview: balance cards, net worth, recent ledger entries and a
     * 30-day projection preview (B5 landing page).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $accounts = $user->accounts()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $recentTransactions = $user->transactions()
            ->with(['account:id,name,type', 'category:id,name'])
            ->whereIn('type', ['income', 'expense'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // Pending one-off cash flows still ahead of us (B6 — the "Upcoming" list).
        $upcomingExpected = $user->expectedTransactions()
            ->with(['account:id,name'])
            ->pending()
            ->whereDate('expected_date', '>=', Carbon::today()->toDateString())
            ->orderBy('expected_date')
            ->limit(6)
            ->get();

        return Inertia::render('Budget/Index', [
            'accounts' => $accounts,
            'summary' => $this->accountService->summary($accounts),
            'recentTransactions' => $recentTransactions,
            'upcomingExpected' => $upcomingExpected,
            'projection' => $this->projectionService->project($user, Carbon::today()->addDays(30)),
        ]);
    }
}
