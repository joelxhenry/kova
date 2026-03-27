<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->index('invoice_id');
        });

        Schema::table('client_contacts', function (Blueprint $table): void {
            $table->index('client_id');
        });

        Schema::table('withholding_credits', function (Blueprint $table): void {
            $table->index('date_withheld');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropIndex(['invoice_id']);
        });

        Schema::table('client_contacts', function (Blueprint $table): void {
            $table->dropIndex(['client_id']);
        });

        Schema::table('withholding_credits', function (Blueprint $table): void {
            $table->dropIndex(['date_withheld']);
        });
    }
};
