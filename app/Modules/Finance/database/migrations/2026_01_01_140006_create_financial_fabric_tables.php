<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transaction_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('transaction_number');
            $table->enum('transaction_domain', ['rental', 'service', 'inventory', 'sales', 'purchase', 'payroll', 'manual'])
                ->comment('Which business domain generated this batch');
            $table->nullableMorphs('source');
            $table->decimal('total_debit', 20, 6)->default('0.000000');
            $table->decimal('total_credit', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'posted', 'reversed', 'failed'])->default('pending');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'ftb_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('rollback_of_batch_id')->nullable()
                ->constrained('financial_transaction_batches', 'id', 'ftb_rollback_of_fk')->nullOnDelete()
                ->comment('Points to the original batch this one reverses');
            $table->foreignId('processed_by')->nullable()->constrained('users', 'id', 'ftb_processed_by_fk')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'transaction_number'], 'ftb_tenant_org_number_uk');
            $table->index(['tenant_id', 'transaction_domain', 'status'], 'ftb_tenant_domain_status_idx');
            $table->index(['tenant_id', 'status', 'processed_at'], 'ftb_tenant_status_date_idx');
        });

        Schema::create('financial_transaction_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('batch_id')
                ->constrained('financial_transaction_batches', 'id', 'fta_batch_id_fk')->cascadeOnDelete();
            $table->enum('line_type', ['customer_charge', 'supplier_payable', 'driver_payable', 'tax', 'commission', 'internal_cost', 'adjustment']);
            $table->string('counterparty_type')->nullable()->comment('customer, supplier, employee');
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->foreignId('account_id')->constrained('accounts', 'id', 'fta_account_id_fk')->cascadeOnDelete();
            $table->decimal('debit_amount', 20, 6)->default('0.000000');
            $table->decimal('credit_amount', 20, 6)->default('0.000000');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'fta_currency_id_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default('1.0000000000');
            $table->enum('settlement_status', ['unsettled', 'partial', 'settled'])->default('unsettled');
            $table->foreignId('settled_payment_id')->nullable()->constrained('payments', 'id', 'fta_settled_payment_id_fk')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'batch_id'], 'fta_tenant_batch_idx');
            $table->index(['tenant_id', 'counterparty_type', 'counterparty_id', 'settlement_status'], 'fta_tenant_counterparty_settlement_idx');
            $table->index(['tenant_id', 'account_id', 'line_type'], 'fta_tenant_account_type_idx');
        });

        Schema::create('financial_rollback_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('batch_id')
                ->constrained('financial_transaction_batches', 'id', 'frl_batch_id_fk')->cascadeOnDelete();
            $table->foreignId('rollback_batch_id')
                ->constrained('financial_transaction_batches', 'id', 'frl_rollback_batch_id_fk')->cascadeOnDelete();
            $table->string('reason_code')->nullable();
            $table->text('reason');
            $table->foreignId('rolled_back_by')->nullable()->constrained('users', 'id', 'frl_rolled_back_by_fk')->nullOnDelete();
            $table->timestamp('rolled_back_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'batch_id', 'rollback_batch_id'], 'frl_tenant_org_batch_rollback_uk');
            $table->index(['tenant_id', 'batch_id'], 'frl_tenant_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_rollback_logs');
        Schema::dropIfExists('financial_transaction_allocations');
        Schema::dropIfExists('financial_transaction_batches');
    }
};
