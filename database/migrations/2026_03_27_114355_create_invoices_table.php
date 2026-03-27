<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('gct_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('contractors_levy_amount', 15, 2)->default(0);
            $table->decimal('net_receivable', 15, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'invoice_number']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'issue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
