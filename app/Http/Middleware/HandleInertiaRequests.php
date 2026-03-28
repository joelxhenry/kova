<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'has_tax_profile' => $request->user()->taxProfile !== null,
                    'is_admin' => $request->user()->is_admin,
                ] : null,
                'taxProfile' => $request->user()?->taxProfile ? [
                    'business_type' => $request->user()->taxProfile->business_type,
                    'is_gct_registered' => $request->user()->taxProfile->is_gct_registered,
                ] : null,
            ],
            'notifications' => [
                'unreadCount' => $request->user()?->unreadNotifications()->count() ?? 0,
                'recent' => $request->user()
                    ? $request->user()->notifications()->orderByDesc('created_at')->limit(5)->get()->map(fn ($n) => [
                        'id' => $n->id,
                        'message' => $n->data['message'] ?? '',
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at->diffForHumans(),
                    ])
                    : [],
            ],
            'settings' => $request->user()?->settings ? [
                'business_name' => $request->user()->settings->get('business_name'),
                'business_logo_path' => $request->user()->settings->get('business_logo_path'),
            ] : null,
            'subscription' => $request->user() ? [
                'subscribed' => $request->user()->subscribed(),
                'onTrial' => $request->user()->onTrial(),
                'trialDaysLeft' => $request->user()->onTrial()
                    ? (int) now()->diffInDays($request->user()->trialEndsAt(), false)
                    : null,
            ] : null,
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
        ];
    }
}
