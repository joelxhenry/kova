<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table): void {
            $table->text('trn')->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->text('trn')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table): void {
            $table->string('trn', 20)->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->string('trn', 20)->nullable()->change();
        });
    }
};
