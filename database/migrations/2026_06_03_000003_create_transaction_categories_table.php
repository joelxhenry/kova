<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_categories', function (Blueprint $table): void {
            $table->id();
            // System defaults are seeded with a null user_id; user-created rows are scoped to the owner.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind'); // income | expense | both
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });

        $now = now();

        DB::table('transaction_categories')->insert([
            // Income defaults
            ['user_id' => null, 'name' => 'Salary', 'kind' => 'income', 'is_default' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Freelance', 'kind' => 'income', 'is_default' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Other Income', 'kind' => 'income', 'is_default' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            // Expense defaults
            ['user_id' => null, 'name' => 'Groceries', 'kind' => 'expense', 'is_default' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Rent', 'kind' => 'expense', 'is_default' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Transport', 'kind' => 'expense', 'is_default' => true, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Utilities', 'kind' => 'expense', 'is_default' => true, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Entertainment', 'kind' => 'expense', 'is_default' => true, 'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Other', 'kind' => 'expense', 'is_default' => true, 'sort_order' => 9, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_categories');
    }
};
