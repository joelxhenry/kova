<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('trn', 20)->nullable();
            $table->enum('business_type', [
                'specified_services',
                'construction',
                'haulage',
                'tillage',
                'other',
            ]);
            $table->boolean('is_gct_registered')->default(false);
            $table->date('gct_registration_date')->nullable();
            $table->decimal('nis_rate', 5, 2)->default(3.00);
            $table->decimal('education_tax_rate', 5, 2)->default(2.25);
            $table->date('fiscal_year_start')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_profiles');
    }
};
