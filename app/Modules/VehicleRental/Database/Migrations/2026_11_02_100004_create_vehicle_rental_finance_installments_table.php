<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_finance_installments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('finance_agreement_id');
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_due', 20, 6)->default('0.000000');
            $table->decimal('interest_due', 20, 6)->default('0.000000');
            $table->decimal('fee_due', 20, 6)->default('0.000000');
            $table->decimal('tax_due', 20, 6)->default('0.000000');
            $table->decimal('total_due', 20, 6)->default('0.000000');
            $table->decimal('paid_amount', 20, 6)->default('0.000000');
            $table->decimal('balance_due', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('scheduled');
            $table->foreignId('invoice_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['finance_agreement_id', 'installment_number'], 'vr_fin_inst_agree_num_uk');
            $table->unique(['id', 'tenant_id'], 'vr_fin_inst_id_tenant_uk');
            $table->index(['due_date', 'status'], 'vr_fin_inst_due_status_ix');
            $table->index(['invoice_id', 'status'], 'vr_fin_inst_inv_status_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_fin_inst_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['finance_agreement_id', 'tenant_id'], 'vr_fin_inst_agree_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_finance_agreements')
                ->cascadeOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'vr_fin_inst_invoice_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'vr_fin_inst_created_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'vr_fin_inst_updated_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_finance_installments');
    }
};
