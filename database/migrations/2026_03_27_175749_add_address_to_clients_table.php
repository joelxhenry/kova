<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('address_line_1')->nullable()->after('is_designated_entity');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state_or_parish')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('state_or_parish');
            $table->string('country', 100)->nullable()->default('Jamaica')->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['address_line_1', 'address_line_2', 'city', 'state_or_parish', 'postal_code', 'country']);
        });
    }
};
