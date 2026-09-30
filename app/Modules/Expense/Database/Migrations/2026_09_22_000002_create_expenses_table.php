<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'expenses_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('expense_type_id');
            $table->string('expense_number', 100);
            $table->date('expense_date');
            $table->decimal('amount', 20, 6);
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', indexName: 'expenses_currency_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 6)->default('1.000000');
            $table->string('expense_type_code_snapshot', 100);
            $table->string('expense_type_name_snapshot');
            $table->unsignedBigInteger('payment_method_id_snapshot');
            $table->string('payment_method_code_snapshot', 100);
            $table->string('payment_method_name_snapshot');
            $table->string('payment_method_type_snapshot', 40);
            $table->string('reference_number', 150)->nullable();
            $table->string('instrument_number', 150)->nullable();
            $table->date('instrument_date')->nullable();
            $table->string('external_bank_name', 150)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('posting');
            $table->string('finance_posting_reference', 160)->nullable();
            $table->string('finance_reversal_reference', 160)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->date('reversal_date')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'expense_number'], 'expenses_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'expense_date'], 'expenses_tenant_org_date_ix');
            $table->index(['tenant_id', 'expense_type_id', 'expense_date'], 'expenses_tenant_type_date_ix');
            $table->index(['tenant_id', 'status', 'expense_date'], 'expenses_tenant_status_date_ix');
            $table->index('finance_posting_reference', 'expenses_finance_posting_reference_ix');
            $table->unique(['id', 'tenant_id'], 'expenses_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'expenses_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['expense_type_id', 'tenant_id'], 'expenses_expense_type_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('expense_types')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'expenses_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'expenses_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'expenses_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
