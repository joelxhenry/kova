<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statutory_rates', function (Blueprint $table): void {
            $table->dropUnique(['key']);
            $table->unique(['key', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::table('statutory_rates', function (Blueprint $table): void {
            $table->dropUnique(['key', 'effective_from']);
            $table->unique('key');
        });
    }
};
