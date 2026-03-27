<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('regular user cannot access admin users', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin/users')->assertStatus(403);
});

test('admin can view users index', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(3)->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 3)
        );
});

test('users index excludes admin users', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['is_admin' => false, 'name' => 'Regular']);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Regular')
        );
});

test('users index supports search', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['name' => 'Alice Johnson', 'is_admin' => false]);
    User::factory()->create(['name' => 'Bob Smith', 'is_admin' => false]);

    $this->actingAs($admin)
        ->get('/admin/users?search=Alice')
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Alice Johnson')
        );
});

test('users index filters by status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['is_admin' => false, 'suspended_at' => null]);
    User::factory()->create(['is_admin' => false, 'suspended_at' => now()]);

    $this->actingAs($admin)
        ->get('/admin/users?status=suspended')
        ->assertInertia(fn ($page) => $page->has('users.data', 1));

    $this->actingAs($admin)
        ->get('/admin/users?status=active')
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('admin can view user detail page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);
    TaxProfile::create(['user_id' => $user->id, 'business_type' => 'specified_services', 'trn' => '123456789']);

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Show')
            ->has('user')
            ->has('stats')
            ->where('user.tax_profile.trn', '123456789')
        );
});

test('user detail shows correct stats', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);
    $client = Client::create(['user_id' => $user->id, 'name' => 'C', 'is_designated_entity' => false]);

    Invoice::create([
        'user_id' => $user->id, 'client_id' => $client->id,
        'invoice_number' => 'INV-0001', 'issue_date' => '2026-01-01',
        'subtotal' => 100000, 'total' => 100000, 'net_receivable' => 100000,
        'status' => 'paid',
    ]);

    $this->actingAs($admin)
        ->get("/admin/users/{$user->id}")
        ->assertInertia(fn ($page) => $page
            ->where('stats.clientCount', 1)
            ->where('stats.invoiceCount', 1)
            ->where('stats.totalInvoiced', 100000)
        );
});

test('admin can suspend a user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'suspended_at' => null]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/suspend")
        ->assertRedirect();

    expect($user->fresh()->suspended_at)->not->toBeNull();
});

test('admin cannot suspend another admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $otherAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post("/admin/users/{$otherAdmin->id}/suspend")
        ->assertSessionHasErrors('user');

    expect($otherAdmin->fresh()->suspended_at)->toBeNull();
});

test('admin can reactivate a suspended user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false, 'suspended_at' => now()]);

    $this->actingAs($admin)
        ->post("/admin/users/{$user->id}/reactivate")
        ->assertRedirect();

    expect($user->fresh()->suspended_at)->toBeNull();
});

test('suspended user is logged out and cannot access app', function () {
    $user = User::factory()->create(['is_admin' => false, 'suspended_at' => now()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/login');
});

test('suspended user sees error on login page', function () {
    $user = User::factory()->create(['is_admin' => false, 'suspended_at' => now()]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect('/login');
});

test('users index is paginated', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(25)->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 20)
            ->where('users.last_page', 2)
        );
});
