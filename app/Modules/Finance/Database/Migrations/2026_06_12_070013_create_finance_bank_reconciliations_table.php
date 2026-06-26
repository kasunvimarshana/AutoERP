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
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'finance_bank_reconciliations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('bank_account_id');
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
            $table->index(['tenant_id', 'organization_unit_id', 'bank_account_id'], 'finance_bank_recon_scope_account_ix');
            $table->index('status', 'finance_bank_recon_status_ix');

            $table->unique(['id', 'tenant_id'], 'finance_bank_reconciliations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'finance_bank_reconciliations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['bank_account_id', 'tenant_id'], 'finance_bank_reconciliations_bank_account_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('finance_accounts')
                ->restrictOnDelete();

            $table->foreign(['completed_by', 'tenant_id'], 'finance_bank_reconciliations_completed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_bank_reconciliations');
    }
};
