<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expected_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // An expected item may be unassigned to an account until it is realized;
            // nullified if the chosen account is later removed.
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // income | expense
            $table->decimal('amount', 15, 2);
            $table->date('expected_date');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending | realized | cancelled
            // Provenance link to the real ledger row created when the item is realized.
            $table->foreignId('realized_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status', 'expected_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expected_transactions');
    }
};
