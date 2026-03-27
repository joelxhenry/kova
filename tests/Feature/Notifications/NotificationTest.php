<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\QuarterlyPaymentReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;

uses(RefreshDatabase::class);

test('guests cannot access notifications', function () {
    $this->get('/notifications')->assertRedirect('/login');
});

test('user can view notifications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/notifications')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications')
        );
});

test('unread count is shared in inertia props', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->has('notifications.unreadCount')
        );
});

test('unread count reflects actual unread notifications', function () {
    $user = User::factory()->create();

    NotificationFacade::sendNow($user, new QuarterlyPaymentReminderNotification(1, '2025-03-15', 100000, 14));
    NotificationFacade::sendNow($user, new QuarterlyPaymentReminderNotification(2, '2025-06-15', 100000, 3));

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unreadCount', 2)
        );
});

test('user can mark a notification as read', function () {
    $user = User::factory()->create();

    NotificationFacade::sendNow($user, new QuarterlyPaymentReminderNotification(1, '2025-03-15', 50000, 14));

    $notification = $user->notifications()->first();
    expect($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->post("/notifications/{$notification->id}/read")
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();

    NotificationFacade::sendNow($user, new QuarterlyPaymentReminderNotification(1, '2025-03-15', 50000, 14));
    NotificationFacade::sendNow($user, new QuarterlyPaymentReminderNotification(2, '2025-06-15', 50000, 3));

    expect($user->unreadNotifications()->count())->toBe(2);

    $this->actingAs($user)
        ->post('/notifications/mark-all-read')
        ->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
