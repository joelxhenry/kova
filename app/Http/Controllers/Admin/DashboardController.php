<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $usersQuery = User::where('is_admin', false);

        $totalUsers = (clone $usersQuery)->count();
        $activeUsers = (clone $usersQuery)->whereNull('suspended_at')->count();
        $suspendedUsers = (clone $usersQuery)->whereNotNull('suspended_at')->count();

        $totalInvoiced = (float) Invoice::where('status', 'paid')->sum('total');
        $invoiceCount = Invoice::count();

        $recentSignups = User::where('is_admin', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at', 'suspended_at']);

        // Monthly signups for the current year
        $year = (int) date('Y');
        $monthlySignups = User::where('is_admin', false)
            ->whereYear('created_at', $year)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('count', 'month');

        $signupChart = [];
        for ($m = 1; $m <= 12; $m++) {
            $signupChart[] = [
                'month' => $m,
                'count' => (int) ($monthlySignups[$m] ?? 0),
            ];
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'suspendedUsers' => $suspendedUsers,
                'totalInvoiced' => round($totalInvoiced, 2),
                'invoiceCount' => $invoiceCount,
            ],
            'recentSignups' => $recentSignups,
            'signupChart' => $signupChart,
            'year' => $year,
        ]);
    }
}
