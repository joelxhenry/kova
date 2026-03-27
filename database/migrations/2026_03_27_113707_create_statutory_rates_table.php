<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->decimal('value', 15, 4);
            $table->string('description')->nullable();
            $table->date('effective_from')->nullable();
            $table->timestamps();
        });

        DB::table('statutory_rates')->insert([
            ['key' => 'nis_rate', 'label' => 'NIS Rate (%)', 'value' => 3.0000, 'description' => 'National Insurance Scheme contribution rate for self-employed', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'education_tax_rate', 'label' => 'Education Tax Rate (%)', 'value' => 2.2500, 'description' => 'Education tax rate for self-employed individuals', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'gct_rate', 'label' => 'GCT Rate (%)', 'value' => 15.0000, 'description' => 'General Consumption Tax standard rate', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'withholding_tax_rate', 'label' => 'Withholding Tax Rate (%)', 'value' => 3.0000, 'description' => 'Withholding tax on specified services over invoice threshold', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contractors_levy_rate', 'label' => 'Contractors Levy Rate (%)', 'value' => 2.0000, 'description' => 'Levy on construction, haulage, and tillage contractors', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tax_free_threshold', 'label' => 'Tax-Free Threshold (JMD)', 'value' => 1700088.0000, 'description' => 'Annual income tax-free threshold', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'tax_bracket_25_limit', 'label' => '25% Bracket Limit (JMD)', 'value' => 6000000.0000, 'description' => 'Income up to this amount taxed at 25% after threshold', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'gct_registration_threshold', 'label' => 'GCT Registration Threshold (JMD)', 'value' => 15000000.0000, 'description' => 'Annual turnover requiring mandatory GCT registration', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'withholding_tax_invoice_threshold', 'label' => 'Withholding Invoice Threshold (JMD)', 'value' => 50000.0000, 'description' => 'Minimum invoice amount for withholding tax to apply', 'effective_from' => '2024-01-01', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rates');
    }
};
