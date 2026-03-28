<?php

namespace App\Providers;

use App\Listeners\HandlePaddleWebhook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Paddle\Events\SubscriptionActivated;
use Laravel\Paddle\Events\SubscriptionCanceled;
use Laravel\Paddle\Events\SubscriptionPastDue;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(SubscriptionActivated::class, [HandlePaddleWebhook::class, 'handleActivated']);
        Event::listen(SubscriptionCanceled::class, [HandlePaddleWebhook::class, 'handleCanceled']);
        Event::listen(SubscriptionPastDue::class, [HandlePaddleWebhook::class, 'handlePastDue']);
    }
}
