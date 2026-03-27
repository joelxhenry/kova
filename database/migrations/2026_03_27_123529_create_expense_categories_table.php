<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('expense_categories')->insert([
            ['user_id' => null, 'name' => 'Equipment', 'description' => 'Tools, machinery, hardware', 'is_default' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Fuel & Transport', 'description' => 'Gas, taxi, vehicle maintenance', 'is_default' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Office Rent', 'description' => 'Workspace rental and coworking', 'is_default' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Software & Subscriptions', 'description' => 'SaaS tools, licenses, hosting', 'is_default' => true, 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Professional Services', 'description' => 'Accounting, legal, consulting', 'is_default' => true, 'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Utilities', 'description' => 'Internet, phone, electricity', 'is_default' => true, 'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'name' => 'Other', 'description' => 'Miscellaneous business expenses', 'is_default' => true, 'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
