<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admins bypass subscription check
        if ($user->is_admin) {
            return $next($request);
        }

        // Allow if subscribed or on trial
        if ($user->subscribed() || $user->onTrial()) {
            return $next($request);
        }

        // Allow access to billing/pricing pages so user can subscribe
        if ($request->routeIs('billing.*')) {
            return $next($request);
        }

        return redirect()->route('billing.pricing');
    }
}
