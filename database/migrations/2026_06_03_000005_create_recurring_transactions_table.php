<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            // Destination account for recurring transfers; nullified if removed.
            $table->foreignId('transfer_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('transaction_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // income | expense | transfer
            $table->decimal('amount', 15, 2);
            $table->string('frequency'); // daily | weekly | biweekly | monthly | yearly
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->date('last_run_date')->nullable();
            $table->string('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
