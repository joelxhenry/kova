<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn(['nis_rate', 'education_tax_rate']);
        });
    }

    public function down(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->decimal('nis_rate', 5, 2)->default(3.00);
            $table->decimal('education_tax_rate', 5, 2)->default(2.25);
        });
    }
};
