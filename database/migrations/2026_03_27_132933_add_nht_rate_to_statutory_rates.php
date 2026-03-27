<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('statutory_rates')->insert([
            'key' => 'nht_rate',
            'label' => 'NHT Rate (%)',
            'value' => 2.0000,
            'description' => 'National Housing Trust contribution rate for self-employed',
            'effective_from' => '2024-01-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('statutory_rates')->where('key', 'nht_rate')->delete();
    }
};
