<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_expenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->string('expense_number', 100);
            $table->foreignId('customer_agreement_id')->nullable();
            $table->foreignId('vehicle_use_id')->nullable();
            $table->foreignId('running_chart_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('vehicle_label_snapshot');
            $table->foreignId('supplier_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->string('expense_type', 30);
            $table->date('expense_date');
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->string('currency_code_snapshot', 10);
            $table->decimal('net_amount', 20, 6);
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('gross_amount', 20, 6);
            $table->string('reference_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->char('fingerprint', 64);
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'expense_number'], 'vr_exp_tenant_num_uk');
            $table->unique(['tenant_id', 'fingerprint'], 'vr_exp_fingerprint_uk');
            $table->unique(['id', 'tenant_id'], 'vr_exp_id_tenant_uk');
            $table->index(['vehicle_id', 'expense_date', 'status'], 'vr_exp_veh_date_status_ix');
            $table->index(['customer_agreement_id', 'expense_type', 'status'], 'vr_exp_agree_type_status_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_exp_org_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_agreement_id', 'tenant_id'], 'vr_exp_agreement_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_agreements')
                ->restrictOnDelete();
            $table->foreign(['vehicle_use_id', 'tenant_id'], 'vr_exp_use_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_uses')
                ->restrictOnDelete();
            $table->foreign(['running_chart_id', 'tenant_id'], 'vr_exp_chart_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_running_charts')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vr_exp_vehicle_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'vr_exp_supplier_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();
            $table->foreign(['employee_id', 'tenant_id'], 'vr_exp_employee_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'vr_exp_tax_group_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();

            $table->foreign(['submitted_by', 'tenant_id'], 'vr_exp_submitted_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['approved_by', 'tenant_id'], 'vr_exp_approved_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['rejected_by', 'tenant_id'], 'vr_exp_rejected_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'vr_exp_reversed_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_exp_created_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['updated_by', 'tenant_id'], 'vr_exp_updated_by_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_expenses');
    }
};
