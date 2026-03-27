<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withholding_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 20); // invoice, manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->integer('tax_year');
            $table->date('date_withheld');
            $table->string('description');
            $table->timestamps();

            $table->index(['user_id', 'tax_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_credits');
    }
};
