<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop removed tables
        Schema::dropIfExists('statutory_rate_audit_log');
        Schema::dropIfExists('statutory_rates');
        Schema::dropIfExists('tax_form_snapshots');
        Schema::dropIfExists('withholding_credits');
        Schema::dropIfExists('tax_profiles');

        // Drop Cashier/Paddle tables
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('customers');

        // Remove tax columns from invoices
        if (Schema::hasColumn('invoices', 'gct_amount')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn(['gct_amount', 'withholding_tax_amount', 'contractors_levy_amount', 'net_receivable']);
            });
        }

        // Create expense_categories table
        if (! Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Seed default categories
            $defaults = [
                ['name' => 'Equipment', 'description' => 'Tools, machinery, hardware', 'sort_order' => 1],
                ['name' => 'Fuel & Transport', 'description' => 'Gas, taxi, vehicle maintenance', 'sort_order' => 2],
                ['name' => 'Office Rent', 'description' => 'Workspace rental and coworking', 'sort_order' => 3],
                ['name' => 'Software & Subscriptions', 'description' => 'SaaS tools, licenses, hosting', 'sort_order' => 4],
                ['name' => 'Professional Services', 'description' => 'Accounting, legal, consulting', 'sort_order' => 5],
                ['name' => 'Utilities', 'description' => 'Internet, phone, electricity', 'sort_order' => 6],
                ['name' => 'Other', 'description' => 'Miscellaneous business expenses', 'sort_order' => 7],
            ];

            foreach ($defaults as $cat) {
                \Illuminate\Support\Facades\DB::table('expense_categories')->insert([
                    ...$cat,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Create expenses table
        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->date('date_incurred');
                $table->string('receipt_path')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'date_incurred']);
                $table->index(['user_id', 'expense_category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
