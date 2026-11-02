<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_expense_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('expense_id');
            $table->unsignedInteger('sequence');
            $table->string('allocation_type', 40);
            $table->foreignId('target_customer_agreement_id')->nullable();
            $table->foreignId('target_owner_agreement_id')->nullable();
            $table->foreignId('target_vehicle_use_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('supplier_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->decimal('net_amount', 20, 6);
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('withholding_amount', 20, 6)->default('0.000000');
            $table->decimal('markup_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6);
            $table->string('status', 30)->default('draft');
            $table->foreignId('invoice_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['expense_id', 'sequence'], 'vr_exp_alloc_seq_uk');
            $table->unique(['id', 'tenant_id'], 'vr_exp_alloc_id_tenant_uk');
            $table->index(['allocation_type', 'status'], 'vr_exp_alloc_type_status_ix');
            $table->index(['invoice_id'], 'vr_exp_alloc_invoice_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_exp_alloc_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['expense_id', 'tenant_id'], 'vr_exp_alloc_expense_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_expenses')
                ->cascadeOnDelete();
            $table->foreign(['target_customer_agreement_id', 'tenant_id'], 'vr_exp_alloc_cust_agree_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_agreements')
                ->restrictOnDelete();
            $table->foreign(['target_owner_agreement_id', 'tenant_id'], 'vr_exp_alloc_owner_agree_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_owner_agreements')
                ->restrictOnDelete();
            $table->foreign(['target_vehicle_use_id', 'tenant_id'], 'vr_exp_alloc_use_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_uses')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'vr_exp_alloc_customer_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'vr_exp_alloc_supplier_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'vr_exp_alloc_employee_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'vr_exp_alloc_tax_group_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
            $table->foreign(['invoice_id', 'tenant_id'], 'vr_exp_alloc_invoice_fk')
                ->references(['id', 'tenant_id'])
                ->on('invoices')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'vr_exp_alloc_created_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'vr_exp_alloc_updated_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_expense_allocations');
    }
};
