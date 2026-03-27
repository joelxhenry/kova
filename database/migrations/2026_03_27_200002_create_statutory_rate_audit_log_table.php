<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rate_audit_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('statutory_rate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_value', 15, 4);
            $table->decimal('new_value', 15, 4);
            $table->date('old_effective_from');
            $table->date('new_effective_from');
            $table->timestamp('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rate_audit_log');
    }
};
