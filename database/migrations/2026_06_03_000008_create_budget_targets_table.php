<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_category_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // income | expense
            $table->string('period')->default('monthly'); // monthly (weekly/yearly reserved)
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            // One planned amount per category & period for a given user.
            $table->unique(['user_id', 'transaction_category_id', 'period']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_targets');
    }
};
