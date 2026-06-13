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
            // How the stored interest_rate is quoted: 'apr' (nominal, compounded
            // monthly) or 'effective' (Effective Annual Rate, already compounded).
            $table->string('rate_basis')->default('apr')->after('interest_rate');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('rate_basis');
        });
    }
};
