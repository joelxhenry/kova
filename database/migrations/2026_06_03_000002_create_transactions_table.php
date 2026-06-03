<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            // Destination account for transfers; nullified if the destination account is removed.
            $table->foreignId('transfer_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            // FK constraint to transaction_categories is added in Phase B2 (table not yet created).
            $table->unsignedBigInteger('transaction_category_id')->nullable();
            $table->string('type'); // income | expense | transfer
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description');
            $table->text('notes')->nullable();
            // FK constraint to recurring_transactions is added in Phase B3 (table not yet created).
            $table->unsignedBigInteger('recurring_transaction_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['account_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
