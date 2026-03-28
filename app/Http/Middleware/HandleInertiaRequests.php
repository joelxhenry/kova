<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
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
                    'is_admin' => $request->user()->is_admin,
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
            ] : null,
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
        ];
    }
}
