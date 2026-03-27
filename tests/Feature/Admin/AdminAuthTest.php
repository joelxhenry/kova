<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot access admin routes', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('regular users cannot access admin routes', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});

test('admin users can access admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats')
            ->has('recentSignups')
        );
});

test('admin dashboard shows correct user count', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(3)->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertInertia(fn ($page) => $page
            ->where('stats.totalUsers', 3)
        );
});

test('admin dashboard shows recent signups', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['name' => 'New User', 'is_admin' => false]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertInertia(fn ($page) => $page
            ->has('recentSignups', 1)
            ->where('recentSignups.0.name', 'New User')
        );
});

test('is_admin is shared in inertia props', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.is_admin', true)
        );
});

test('regular user is_admin is false in props', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.is_admin', false)
        );
});
