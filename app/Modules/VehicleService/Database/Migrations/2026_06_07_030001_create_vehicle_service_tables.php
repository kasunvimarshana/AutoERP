<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('job_number', 100);
            $table->date('job_date');
            $table->date('expected_delivery_date')->nullable();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('supervisor_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('supervisor_commission_type', 20)->default('none');
            $table->decimal('supervisor_commission_value', 20, 6)->default('0.000000');
            $table->decimal('supervisor_commission_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('draft');
            $table->decimal('odometer_reading', 20, 6)->nullable();
            $table->string('fuel_level')->nullable();
            $table->string('priority', 30)->nullable();
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('discount_total', 20, 6)->default('0.000000');
            $table->decimal('tax_total', 20, 6)->default('0.000000');
            $table->decimal('charge_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'job_number'], 'vehicle_service_jobs_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_jobs_tenant_org_idx');
            $table->index(['customer_id', 'vehicle_id'], 'vehicle_service_jobs_party_vehicle_idx');
            $table->index(['status', 'job_date'], 'vehicle_service_jobs_status_date_idx');
        });

        Schema::create('vehicle_service_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->text('customer_complaint')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('recommended_work')->nullable();
            $table->decimal('odometer_reading', 20, 6)->nullable();
            $table->string('fuel_level')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->unique('vehicle_service_job_id', 'vehicle_service_inspections_job_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_inspections_tenant_org_idx');
        });

        Schema::create('vehicle_service_job_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('parent_line_id')->nullable()->constrained('vehicle_service_job_lines')->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('line_source_type', 30);
            $table->foreignId('item_id')->nullable()->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('unit_price', 20, 6)->default('0.000000');
            $table->string('discount_calculation_type', 20)->nullable();
            $table->decimal('discount_rate', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->string('tax_calculation_type', 20)->nullable();
            $table->decimal('tax_rate', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->string('charge_calculation_type', 20)->nullable();
            $table->decimal('charge_rate', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6)->default('0.000000');
            $table->boolean('is_inventory_tracked')->default(false);
            $table->boolean('is_customer_supplied')->default(false);
            $table->boolean('is_external')->default(false);
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_employee_assignable')->default(false);
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'line_number'], 'vehicle_service_job_lines_job_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_job_lines_tenant_org_idx');
            $table->index(['vehicle_service_job_id', 'line_source_type'], 'vehicle_service_job_lines_job_type_idx');
            $table->index('parent_line_id', 'vehicle_service_job_lines_parent_idx');
        });

        Schema::create('vehicle_service_line_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('vehicle_service_job_line_id')->constrained('vehicle_service_job_lines')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->restrictOnDelete();
            $table->string('role_type', 30);
            $table->decimal('assigned_hours', 20, 6)->default('0.000000');
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->string('commission_type', 20)->default('none');
            $table->decimal('commission_value', 20, 6)->default('0.000000');
            $table->decimal('commission_amount', 20, 6)->default('0.000000');
            $table->string('status', 30)->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['vehicle_service_job_line_id', 'employee_id', 'role_type'],
                'vehicle_service_line_employees_assignment_uk',
            );
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_line_employees_tenant_org_idx');
        });

        Schema::create('vehicle_service_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->string('file_path')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_documents_tenant_org_idx');
            $table->index('vehicle_service_job_id', 'vehicle_service_documents_job_idx');
        });

        Schema::create('vehicle_service_invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->decimal('source_line_total', 20, 6);
            $table->decimal('allocated_adjustment_total', 20, 6)->default('0.000000');
            $table->decimal('invoice_total', 20, 6);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'invoice_id'], 'vehicle_service_invoice_links_job_invoice_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_invoice_links_tenant_org_idx');
        });

        Schema::create('vehicle_service_payment_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('allocated_amount', 20, 6);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['vehicle_service_job_id', 'payment_id', 'invoice_id'], 'vehicle_service_payment_links_trace_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_payment_links_tenant_org_idx');
        });

        Schema::create('vehicle_service_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_status_histories_tenant_org_idx');
            $table->index(['vehicle_service_job_id', 'changed_at'], 'vehicle_service_status_histories_job_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_status_histories');
        Schema::dropIfExists('vehicle_service_payment_links');
        Schema::dropIfExists('vehicle_service_invoice_links');
        Schema::dropIfExists('vehicle_service_documents');
        Schema::dropIfExists('vehicle_service_line_employees');
        Schema::dropIfExists('vehicle_service_job_lines');
        Schema::dropIfExists('vehicle_service_inspections');
        Schema::dropIfExists('vehicle_service_jobs');
    }
};
