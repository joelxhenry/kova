<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Events\SubscriptionActivated;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionPastDue;

class HandlePaddleWebhook
{
    public function handleActivated(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;
        $user = User::find($subscription->billable_id);

        if ($user) {
            Log::info("Subscription activated for {$user->email}");
        }
    }

    public function handleCanceled(SubscriptionCanceled $event): void
    {
        $subscription = $event->subscription;
        $user = User::find($subscription->billable_id);

        if ($user) {
            Log::info("Subscription cancelled for {$user->email}");
        }
    }

    public function handlePastDue(SubscriptionPastDue $event): void
    {
        $subscription = $event->subscription;
        $user = User::find($subscription->billable_id);

        if ($user) {
            Log::warning("Subscription past due for {$user->email}");
        }
    }
}
