<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'bank_reconciliations_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained(null, 'id', 'bank_reconciliations_bank_account_id_fk')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('opening_balance', 20, 6);
            $table->decimal('closing_balance', 20, 6);
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->foreignId('completed_by')->nullable()->constrained('users', 'id', 'bank_reconciliations_completed_by_fk')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
