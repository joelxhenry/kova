<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
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

        return Inertia::render('Dashboard', [
            'recentInvoices' => $recentInvoices,
            'recentExpenses' => $recentExpenses,
        ]);
    }
}
