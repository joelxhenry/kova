<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `transactions.transaction_category_id` column was created (without a
     * constraint) in Phase B1. Now that `transaction_categories` exists, promote
     * it to a real foreign key.
     *
     * SQLite cannot add a foreign key to an existing table via ALTER TABLE; the
     * application-level `exists:transaction_categories,id` validation rule
     * enforces referential integrity there, so we skip the constraint.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->foreign('transaction_category_id')
                ->references('id')
                ->on('transaction_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['transaction_category_id']);
        });
    }
};
