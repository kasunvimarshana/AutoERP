<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_bank_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->foreignId('bank_account_id')->constrained('finance_accounts', 'id')->restrictOnDelete();
            $table->string('statement_reference', 150);
            $table->date('statement_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('opening_balance', 20, 6)->default('0.000000');
            $table->decimal('closing_balance', 20, 6)->default('0.000000');
            $table->decimal('reconciled_balance', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'organization_unit_id', 'bank_account_id', 'statement_reference'],
                'finance_bank_recon_scope_statement_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id', 'bank_account_id'], 'finance_bank_recon_scope_account_idx');
            $table->index('status', 'finance_bank_recon_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_reconciliations');
    }
};
