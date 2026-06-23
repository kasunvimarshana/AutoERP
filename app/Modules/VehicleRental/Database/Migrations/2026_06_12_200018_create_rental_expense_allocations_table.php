<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_expense_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('expense_id');
            $table->unsignedInteger('sequence');
            $table->string('allocation_type', 40);
            $table->foreignId('target_agreement_id')->nullable();
            $table->foreignId('target_vehicle_allocation_id')->nullable();
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
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['expense_id', 'sequence'], 'rental_expense_allocations_sequence_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'rental_expense_allocations_fingerprint_uk');
            $table->index(['allocation_type', 'status'], 'rental_expense_allocations_type_status_idx');

            $table->unique(['id', 'tenant_id'], 'rental_expense_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'rental_expense_allocations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['expense_id', 'tenant_id'], 'rental_expense_allocations_expense_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_expenses')
                ->cascadeOnDelete();
            $table->foreign(['target_agreement_id', 'tenant_id'], 'rental_expense_allocations_target_agreement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_agreements')
                ->restrictOnDelete();
            $table->foreign(['target_vehicle_allocation_id', 'tenant_id'], 'rental_expense_allocations_target_vehicle_alloca_bb603e58_fk')
                ->references(['id', 'tenant_id'])
                ->on('rental_vehicle_allocations')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'rental_expense_allocations_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'rental_expense_allocations_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'rental_expense_allocations_employee_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'rental_expense_allocations_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'rental_expense_allocations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'rental_expense_allocations_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_expense_allocations');
    }
};
