<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AccountService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $recentInvoices = $user->invoices()
            ->with('client:id,name')
            ->orderByDesc('issue_date')
            ->limit(5)
            ->get();

        $recentExpenses = $user->expenses()
            ->with('category:id,name')
            ->orderByDesc('date_incurred')
            ->limit(5)
            ->get();

        $accounts = $user->accounts()->get();

        return Inertia::render('Dashboard', [
            'recentInvoices' => $recentInvoices,
            'recentExpenses' => $recentExpenses,
            // Net-worth / cash-on-hand tie-in; null until the user adds accounts.
            'budgetSummary' => $accounts->isNotEmpty()
                ? $this->accountService->summary($accounts)
                : null,
        ]);
    }
}
