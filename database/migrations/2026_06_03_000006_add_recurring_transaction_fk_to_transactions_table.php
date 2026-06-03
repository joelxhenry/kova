<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `transactions.recurring_transaction_id` column was created (without a
     * constraint) in Phase B1. Now that `recurring_transactions` exists, promote
     * it to a real foreign key so a deleted rule detaches (but never removes) the
     * ledger rows it generated (FR-3.4).
     *
     * SQLite cannot add a foreign key to an existing table via ALTER TABLE; the
     * application layer keeps these in sync, so we skip the constraint there.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreign('recurring_transaction_id')
                ->references('id')
                ->on('recurring_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['recurring_transaction_id']);
        });
    }
};
