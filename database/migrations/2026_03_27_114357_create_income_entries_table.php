<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('date_received');
            $table->decimal('withholding_tax_applied', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'date_received']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_entries');
    }
};
