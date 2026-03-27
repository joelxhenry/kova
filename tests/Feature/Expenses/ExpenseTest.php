<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests cannot access expenses', function () {
    $this->get('/expenses')->assertRedirect('/login');
});

test('user can view expenses index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/expenses')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Expenses/Index'));
});

test('default expense categories are seeded', function () {
    expect(ExpenseCategory::whereNull('user_id')->where('is_default', true)->count())->toBe(7);
});

test('index returns categories and totals', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    Expense::create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'description' => 'Test expense',
        'amount' => 5000,
        'date_incurred' => '2025-06-01',
    ]);

    $this->actingAs($user)
        ->get('/expenses')
        ->assertInertia(fn ($page) => $page
            ->component('Expenses/Index')
            ->has('categories')
            ->has('totals')
        );
});

test('user can create an expense', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    $this->actingAs($user)
        ->post('/expenses', [
            'expense_category_id' => $category->id,
            'description' => 'Office chair',
            'amount' => 15000.00,
            'date_incurred' => '2025-06-15',
            'notes' => 'Ergonomic chair for home office',
        ])
        ->assertRedirect('/expenses');

    $this->assertDatabaseHas('expenses', [
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'description' => 'Office chair',
        'amount' => 15000.00,
    ]);
});

test('expense requires description and amount', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    $this->actingAs($user)
        ->post('/expenses', [
            'expense_category_id' => $category->id,
            'description' => '',
            'amount' => '',
            'date_incurred' => '2025-06-15',
        ])
        ->assertSessionHasErrors(['description', 'amount']);
});

test('expense category must exist and be accessible to user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/expenses', [
            'expense_category_id' => 99999,
            'description' => 'Test',
            'amount' => 100,
            'date_incurred' => '2025-06-15',
        ])
        ->assertSessionHasErrors('expense_category_id');
});

test('user can upload a receipt', function () {
    Storage::fake('private');
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    $this->actingAs($user)
        ->post('/expenses', [
            'expense_category_id' => $category->id,
            'description' => 'Fuel',
            'amount' => 3000,
            'date_incurred' => '2025-06-15',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 500, 'application/pdf'),
        ])
        ->assertRedirect('/expenses');

    $expense = $user->expenses()->first();
    expect($expense->receipt_path)->not->toBeNull();
    Storage::disk('private')->assertExists($expense->receipt_path);
});

test('receipt must be valid file type', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    $this->actingAs($user)
        ->post('/expenses', [
            'expense_category_id' => $category->id,
            'description' => 'Test',
            'amount' => 100,
            'date_incurred' => '2025-06-15',
            'receipt' => UploadedFile::fake()->create('doc.txt', 100, 'text/plain'),
        ])
        ->assertSessionHasErrors('receipt');
});

test('receipt must not exceed 5MB', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    $this->actingAs($user)
        ->post('/expenses', [
            'expense_category_id' => $category->id,
            'description' => 'Test',
            'amount' => 100,
            'date_incurred' => '2025-06-15',
            'receipt' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'),
        ])
        ->assertSessionHasErrors('receipt');
});

test('user can update an expense', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();
    $expense = Expense::create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'description' => 'Old',
        'amount' => 100,
        'date_incurred' => '2025-01-01',
    ]);

    $this->actingAs($user)
        ->put("/expenses/{$expense->id}", [
            'expense_category_id' => $category->id,
            'description' => 'Updated',
            'amount' => 200,
            'date_incurred' => '2025-02-01',
        ])
        ->assertRedirect('/expenses');

    expect($expense->fresh()->description)->toBe('Updated')
        ->and((float) $expense->fresh()->amount)->toBe(200.00);
});

test('user cannot access another users expense', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();
    $expense = Expense::create([
        'user_id' => $other->id,
        'expense_category_id' => $category->id,
        'description' => 'Other',
        'amount' => 100,
        'date_incurred' => '2025-01-01',
    ]);

    $this->actingAs($user)->get("/expenses/{$expense->id}/edit")->assertStatus(403);
});

test('user can delete an expense', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();
    $expense = Expense::create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
        'description' => 'Delete me',
        'amount' => 100,
        'date_incurred' => '2025-01-01',
    ]);

    $this->actingAs($user)->delete("/expenses/{$expense->id}")->assertRedirect('/expenses');
    $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
});

test('deleting an expense removes the receipt file', function () {
    Storage::fake('private');
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    $this->actingAs($user)->post('/expenses', [
        'expense_category_id' => $category->id,
        'description' => 'With receipt',
        'amount' => 500,
        'date_incurred' => '2025-06-15',
        'receipt' => UploadedFile::fake()->create('receipt.jpg', 200, 'image/jpeg'),
    ]);

    $expense = $user->expenses()->first();
    $receiptPath = $expense->receipt_path;
    Storage::disk('private')->assertExists($receiptPath);

    $this->actingAs($user)->delete("/expenses/{$expense->id}");
    Storage::disk('private')->assertMissing($receiptPath);
});

test('index filters by category', function () {
    $user = User::factory()->create();
    $cat1 = ExpenseCategory::whereNull('user_id')->first();
    $cat2 = ExpenseCategory::whereNull('user_id')->skip(1)->first();

    Expense::create(['user_id' => $user->id, 'expense_category_id' => $cat1->id, 'description' => 'A', 'amount' => 100, 'date_incurred' => '2025-06-01']);
    Expense::create(['user_id' => $user->id, 'expense_category_id' => $cat2->id, 'description' => 'B', 'amount' => 200, 'date_incurred' => '2025-06-01']);

    $this->actingAs($user)
        ->get("/expenses?category_id={$cat1->id}")
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 1)
        );
});

test('index filters by date range', function () {
    $user = User::factory()->create();
    $category = ExpenseCategory::whereNull('user_id')->first();

    Expense::create(['user_id' => $user->id, 'expense_category_id' => $category->id, 'description' => 'Jan', 'amount' => 100, 'date_incurred' => '2025-01-15']);
    Expense::create(['user_id' => $user->id, 'expense_category_id' => $category->id, 'description' => 'Jun', 'amount' => 200, 'date_incurred' => '2025-06-15']);

    $this->actingAs($user)
        ->get('/expenses?from=2025-06-01&to=2025-06-30')
        ->assertInertia(fn ($page) => $page
            ->has('expenses.data', 1)
            ->where('expenses.data.0.description', 'Jun')
        );
});
