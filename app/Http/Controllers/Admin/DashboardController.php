<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        $recentSignups = User::where('is_admin', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at', 'suspended_at']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'suspendedUsers' => $suspendedUsers,
            ],
            'recentSignups' => $recentSignups,
        ]);
    }
}
