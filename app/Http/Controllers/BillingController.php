<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function pricing(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Billing/Pricing', [
            'prices' => [
                'monthly' => config('cashier.prices.monthly'),
                'yearly' => config('cashier.prices.yearly'),
            ],
            'isSubscribed' => $user->subscribed(),
            'onTrial' => $user->onTrial(),
        ]);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $subscription = $user->subscription();

        $subscriptionData = null;
        if ($subscription) {
            $subscriptionData = [
                'status' => $subscription->status,
                'onGracePeriod' => $subscription->onGracePeriod(),
                'endsAt' => $subscription->ends_at?->toDateString(),
                'nextBilledAt' => $subscription->next_billed_at?->toDateString(),
                'currentPriceId' => $subscription->items->first()?->price_id,
            ];
        }

        $transactions = $user->transactions()->take(10)->get()->map(fn ($t) => [
            'id' => $t->id,
            'status' => $t->status,
            'billed_at' => $t->billed_at?->toDateString(),
        ]);

        return Inertia::render('Billing/Index', [
            'subscription' => $subscriptionData,
            'transactions' => $transactions,
            'prices' => [
                'monthly' => config('cashier.prices.monthly'),
                'yearly' => config('cashier.prices.yearly'),
            ],
            'onTrial' => $user->onTrial(),
            'trialEndsAt' => $user->trialEndsAt()?->toDateString(),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->user()->subscription()?->cancel();

        return back()->with('status', 'Subscription cancelled. You will retain access until the end of your billing period.');
    }

    public function resume(Request $request): RedirectResponse
    {
        $request->user()->subscription()?->resume();

        return back()->with('status', 'Subscription resumed.');
    }

    public function swap(Request $request): RedirectResponse
    {
        $request->validate([
            'price_id' => ['required', 'string'],
        ]);

        $request->user()->subscription()?->swap([$request->input('price_id')]);

        return back()->with('status', 'Plan updated.');
    }
}
