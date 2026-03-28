<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::where('is_admin', false)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->input('status') === 'suspended') {
            $query->whereNotNull('suspended_at');
        } elseif ($request->input('status') === 'active') {
            $query->whereNull('suspended_at');
        }

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('settings');

        $stats = [
            'clientCount' => $user->clients()->count(),
            'invoiceCount' => $user->invoices()->count(),
        ];

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'suspended_at' => $user->suspended_at,
                'created_at' => $user->created_at,
                'business_name' => $user->settings?->get('business_name'),
            ],
            'stats' => $stats,
        ]);
    }

    public function suspend(User $user): RedirectResponse
    {
        if ($user->is_admin) {
            return back()->withErrors(['user' => 'Cannot suspend an admin user.']);
        }

        $user->update(['suspended_at' => now()]);

        return back()->with('status', "{$user->name} has been suspended.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $user->update(['suspended_at' => null]);

        return back()->with('status', "{$user->name} has been reactivated.");
    }
}
