<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expected_transactions', function (Blueprint $table): void {
            // Destination account for a `type=transfer` expected item (a planned
            // payment): money moves from account_id (funding) into this account.
            $table->foreignId('transfer_account_id')->nullable()->after('account_id')
                ->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expected_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transfer_account_id');
        });
    }
};
