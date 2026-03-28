<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop tax-related tables
        Schema::dropIfExists('statutory_rate_audit_log');
        Schema::dropIfExists('statutory_rates');
        Schema::dropIfExists('tax_profiles');
        Schema::dropIfExists('tax_form_snapshots');
        Schema::dropIfExists('withholding_credits');

        // Drop billing/Cashier tables
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

        // Recreate expense tables if they don't exist
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
            \Illuminate\Support\Facades\DB::table('expense_categories')->insert([
                ['name' => 'Equipment', 'description' => 'Tools, machinery, hardware', 'is_default' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Fuel & Transport', 'description' => 'Gas, taxi, vehicle maintenance', 'is_default' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Office Rent', 'description' => 'Workspace rental and coworking', 'is_default' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Software & Subscriptions', 'description' => 'SaaS tools, licenses, hosting', 'is_default' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Professional Services', 'description' => 'Accounting, legal, consulting', 'is_default' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Utilities', 'description' => 'Internet, phone, electricity', 'is_default' => true, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Other', 'description' => 'Miscellaneous business expenses', 'is_default' => true, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

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
        // Not reversible — this is a one-way rebrand migration
    }
};
