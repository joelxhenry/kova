<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access billing pages', function () {
    $this->get('/billing')->assertRedirect('/login');
    $this->get('/billing/pricing')->assertRedirect('/login');
});

test('user can view pricing page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing/pricing')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Billing/Pricing')
            ->has('prices')
            ->has('isSubscribed')
            ->has('onTrial')
        );
});

test('user can view billing page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Billing/Index')
            ->has('subscription')
            ->has('transactions')
            ->has('prices')
        );
});

test('pricing page includes configured price ids', function () {
    $user = User::factory()->create();

    config(['cashier.prices.monthly' => 'pri_monthly_test']);
    config(['cashier.prices.yearly' => 'pri_yearly_test']);

    $this->actingAs($user)
        ->get('/billing/pricing')
        ->assertInertia(fn ($page) => $page
            ->where('prices.monthly', 'pri_monthly_test')
            ->where('prices.yearly', 'pri_yearly_test')
        );
});

test('subscription status shared in inertia props', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('subscription')
            ->where('subscription.subscribed', false)
            ->where('subscription.onTrial', false)
        );
});

test('ensure subscribed middleware allows admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $middleware = new \App\Http\Middleware\EnsureSubscribed();
    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setUserResolver(fn () => $admin);

    $response = $middleware->handle($request, fn ($req) => new \Illuminate\Http\Response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('ensure subscribed middleware redirects unsubscribed users', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $middleware = new \App\Http\Middleware\EnsureSubscribed();
    $request = \Illuminate\Http\Request::create('/dashboard');
    $request->setUserResolver(fn () => $user);
    $request->setRouteResolver(fn () => new \Illuminate\Routing\Route('GET', '/dashboard', []));

    $response = $middleware->handle($request, fn ($req) => new \Illuminate\Http\Response('ok'));

    expect($response->getStatusCode())->toBe(302);
});

test('ensure subscribed middleware allows billing routes', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $middleware = new \App\Http\Middleware\EnsureSubscribed();
    $request = \Illuminate\Http\Request::create('/billing/pricing');
    $request->setUserResolver(fn () => $user);

    $route = new \Illuminate\Routing\Route('GET', '/billing/pricing', []);
    $route->name('billing.pricing');
    $request->setRouteResolver(fn () => $route);

    $response = $middleware->handle($request, fn ($req) => new \Illuminate\Http\Response('ok'));

    expect($response->getContent())->toBe('ok');
});
