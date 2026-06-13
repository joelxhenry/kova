<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            // Annual interest rate as a percentage. Applies to credit-card /
            // loan interest as well as savings interest on debit accounts.
            $table->decimal('interest_rate', 6, 3)->nullable()->after('current_balance');
            // How the stored rate is quoted: 'apr' (nominal, compounded monthly)
            // or 'effective' (Effective Annual Rate, already compounded).
            $table->string('rate_basis')->default('apr')->after('interest_rate');
            // Spending limit for credit accounts (credit cards / lines of credit).
            $table->decimal('credit_limit', 15, 2)->nullable()->after('rate_basis');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn(['interest_rate', 'rate_basis', 'credit_limit']);
        });
    }
};
