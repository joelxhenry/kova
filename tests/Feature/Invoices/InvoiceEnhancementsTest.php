<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserSetting;
use App\Mail\InvoiceEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createTestUserWithClient(array $profileOverrides = [], array $clientOverrides = []): array
{
    $user = User::factory()->create();
    $client = Client::create([
        'user_id' => $user->id,
        'name' => 'Test Client',
        'email' => 'client@test.com',
        ...$clientOverrides,
    ]);

    return [$user, $client];
}

// --- Unit field tests ---

test('invoice item unit field persists on create', function () {
    [$user, $client] = createTestUserWithClient();

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
        'items' => [
            ['description' => 'Consulting', 'unit' => 'hours', 'quantity' => 10, 'unit_price' => 5000],
            ['description' => 'Setup fee', 'quantity' => 1, 'unit_price' => 10000],
        ],
    ]);

    $invoice = $user->invoices()->first();
    $items = $invoice->items()->orderBy('sort_order')->get();

    expect($items[0]->unit)->toBe('hours')
        ->and($items[1]->unit)->toBeNull();
});

test('invoice item unit field persists on update', function () {
    [$user, $client] = createTestUserWithClient();

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
        'items' => [
            ['description' => 'Work', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);

    $invoice = $user->invoices()->first();

    $this->actingAs($user)->put("/invoices/{$invoice->id}", [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
        'items' => [
            ['description' => 'Updated work', 'unit' => 'days', 'quantity' => 5, 'unit_price' => 2000],
        ],
    ]);

    $item = $invoice->fresh()->items->first();
    expect($item->unit)->toBe('days')
        ->and($item->description)->toBe('Updated work');
});

// --- Invoice number from user settings ---

test('invoice number uses user settings configuration', function () {
    [$user, $client] = createTestUserWithClient();

    UserSetting::create([
        'user_id' => $user->id,
        'settings' => [
            'invoice_prefix' => 'KV',
            'invoice_separator' => '/',
            'invoice_next_number' => 42,
            'invoice_padding' => 3,
        ],
    ]);

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
        'items' => [
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);

    $invoice = $user->invoices()->first();
    expect($invoice->invoice_number)->toBe('KV/042');
});

test('invoice number auto-increments via user settings', function () {
    [$user, $client] = createTestUserWithClient();

    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id,
        'issue_date' => '2025-01-01',
        'items' => [['description' => 'A', 'quantity' => 1, 'unit_price' => 100]],
    ]);
    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id,
        'issue_date' => '2025-02-01',
        'items' => [['description' => 'B', 'quantity' => 1, 'unit_price' => 200]],
    ]);

    $numbers = $user->invoices()->pluck('invoice_number')->toArray();
    expect($numbers)->toContain('INV-0001', 'INV-0002');

    // Settings should have incremented to 3
    $setting = UserSetting::where('user_id', $user->id)->first();
    expect($setting->get('invoice_next_number'))->toBe(3);
});

// --- Status update ---

test('user can update invoice status', function () {
    [$user, $client] = createTestUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 10000,
        'total' => 10000,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->put("/invoices/{$invoice->id}/status", ['status' => 'sent'])
        ->assertRedirect("/invoices/{$invoice->id}");

    expect($invoice->fresh()->status)->toBe('sent');
});

test('status update rejects invalid status', function () {
    [$user, $client] = createTestUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 10000,
        'total' => 10000,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->put("/invoices/{$invoice->id}/status", ['status' => 'invalid'])
        ->assertSessionHasErrors('status');
});

test('user cannot update another users invoice status', function () {
    $user = User::factory()->create();
    [$other, $client] = createTestUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $other->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 10000,
        'total' => 10000,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->put("/invoices/{$invoice->id}/status", ['status' => 'sent'])
        ->assertStatus(403);
});

// --- Duplicate ---

test('user can duplicate an invoice', function () {
    [$user, $client] = createTestUserWithClient();

    // Create original via the API so settings increment properly
    $this->actingAs($user)->post('/invoices', [
        'client_id' => $client->id,
        'issue_date' => '2025-06-01',
        'status' => 'paid',
        'notes' => 'Original notes',
        'items' => [
            ['description' => 'Consulting', 'unit' => 'hours', 'quantity' => 10, 'unit_price' => 1000],
        ],
    ]);

    $original = $user->invoices()->first();

    $this->actingAs($user)
        ->post("/invoices/{$original->id}/duplicate")
        ->assertRedirect();

    expect($user->invoices()->count())->toBe(2);

    $duplicate = $user->invoices()->where('id', '!=', $original->id)->first();
    expect($duplicate->status)->toBe('draft')
        ->and($duplicate->client_id)->toBe($client->id)
        ->and($duplicate->notes)->toBe('Original notes')
        ->and($duplicate->items->first()->unit)->toBe('hours')
        ->and($duplicate->items->first()->description)->toBe('Consulting');
});

test('user cannot duplicate another users invoice', function () {
    $user = User::factory()->create();
    [$other, $client] = createTestUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $other->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/duplicate")
        ->assertStatus(403);
});

// --- Send email ---

test('user can send invoice by email', function () {
    Mail::fake();

    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 10000,
        'total' => 10000,
        'status' => 'draft',
    ]);
    $invoice->items()->create([
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 10000,
        'amount' => 10000,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/send", [
            'recipients' => ['billing@acme.com'],
        ])
        ->assertRedirect("/invoices/{$invoice->id}");

    Mail::assertSent(InvoiceEmail::class, function ($mail) {
        return $mail->hasTo('billing@acme.com');
    });

    // Should auto-update status to sent
    expect($invoice->fresh()->status)->toBe('sent');
});

test('send does not change status if already sent', function () {
    Mail::fake();

    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 10000,
        'total' => 10000,
        'status' => 'paid',
    ]);
    $invoice->items()->create([
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 10000,
        'amount' => 10000,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)->post("/invoices/{$invoice->id}/send", [
        'recipients' => ['billing@acme.com'],
    ]);

    expect($invoice->fresh()->status)->toBe('paid');
});

test('send email includes client contacts', function () {
    Mail::fake();

    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);
    ClientContact::create([
        'client_id' => $client->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@acme.com',
    ]);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 5000,
        'total' => 5000,
        'status' => 'draft',
    ]);
    $invoice->items()->create([
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 5000,
        'amount' => 5000,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)->post("/invoices/{$invoice->id}/send", [
        'recipients' => ['billing@acme.com', 'john@acme.com'],
    ]);

    Mail::assertSent(InvoiceEmail::class, function ($mail) {
        return $mail->hasTo('billing@acme.com') && $mail->hasTo('john@acme.com');
    });
});

test('send requires at least one recipient', function () {
    Mail::fake();

    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 5000,
        'total' => 5000,
        'status' => 'draft',
    ]);
    $invoice->items()->create([
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 5000,
        'amount' => 5000,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/send", ['recipients' => []])
        ->assertSessionHasErrors('recipients');

    Mail::assertNothingSent();
});

test('send validates recipient emails', function () {
    Mail::fake();

    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 5000,
        'total' => 5000,
        'status' => 'draft',
    ]);
    $invoice->items()->create([
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 5000,
        'amount' => 5000,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)
        ->post("/invoices/{$invoice->id}/send", ['recipients' => ['not-an-email']])
        ->assertSessionHasErrors('recipients.0');

    Mail::assertNothingSent();
});

// --- Show page ---

test('show page includes business settings', function () {
    [$user, $client] = createTestUserWithClient();

    UserSetting::create([
        'user_id' => $user->id,
        'settings' => [
            'business_name' => 'Kova Ltd',
            'business_phone' => '876-555-0000',
        ],
    ]);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($user)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->component('Invoices/Show')
            ->has('business')
            ->where('business.business_name', 'Kova Ltd')
            ->where('business.business_phone', '876-555-0000')
        );
});

test('show page loads client contacts', function () {
    [$user, $client] = createTestUserWithClient();
    ClientContact::create(['client_id' => $client->id, 'first_name' => 'Alice', 'last_name' => 'Brown']);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($user)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->has('invoice.client.contacts', 1)
        );
});

// --- PDF ---

test('user can download invoice PDF', function () {
    [$user, $client] = createTestUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 10000,
        'total' => 10000,
        'status' => 'sent',
    ]);
    $invoice->items()->create([
        'description' => 'Consulting',
        'unit' => 'hours',
        'quantity' => 10,
        'unit_price' => 1000,
        'amount' => 10000,
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($user)
        ->get("/invoices/{$invoice->id}/pdf");

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('INV-0001.pdf');
});

test('user cannot download another users invoice PDF', function () {
    $user = User::factory()->create();
    [$other, $client] = createTestUserWithClient();

    $invoice = Invoice::create([
        'user_id' => $other->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($user)
        ->get("/invoices/{$invoice->id}/pdf")
        ->assertStatus(403);
});

test('invoice email includes PDF attachment', function () {
    Mail::fake();

    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 5000,
        'total' => 5000,
        'status' => 'draft',
    ]);
    $invoice->items()->create([
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 5000,
        'amount' => 5000,
        'sort_order' => 0,
    ]);

    $this->actingAs($user)->post("/invoices/{$invoice->id}/send", [
        'recipients' => ['billing@acme.com'],
    ]);

    Mail::assertSent(InvoiceEmail::class, function ($mail) {
        $attachments = $mail->attachments();
        return count($attachments) === 1;
    });
});

test('show page includes available recipients', function () {
    [$user, $client] = createTestUserWithClient([], ['email' => 'billing@acme.com']);
    ClientContact::create([
        'client_id' => $client->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@acme.com',
    ]);
    ClientContact::create([
        'client_id' => $client->id,
        'first_name' => 'No',
        'last_name' => 'Email',
    ]);

    $invoice = Invoice::create([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'invoice_number' => 'INV-0001',
        'issue_date' => '2025-06-01',
        'subtotal' => 1000,
        'total' => 1000,
    ]);

    $this->actingAs($user)
        ->get("/invoices/{$invoice->id}")
        ->assertInertia(fn ($page) => $page
            ->has('availableRecipients', 2)
            ->where('availableRecipients.0.email', 'billing@acme.com')
            ->where('availableRecipients.0.type', 'client')
            ->where('availableRecipients.1.email', 'john@acme.com')
            ->where('availableRecipients.1.type', 'contact')
        );
});
