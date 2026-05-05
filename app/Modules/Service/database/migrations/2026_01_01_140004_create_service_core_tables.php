<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_maintenance_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('plan_name');
            $table->string('plan_code');
            $table->text('description')->nullable();

            // Scope
            $table->foreignId('asset_id')->nullable()->constrained('assets', 'id', 'smp_asset_id_fk')->nullOnDelete()
                ->comment('Asset-specific maintenance plan');
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'smp_product_id_fk')->nullOnDelete()
                ->comment('Product-category maintenance plan applied to all matching assets');

            // Trigger configuration
            $table->enum('trigger_type', ['time', 'odometer', 'engine_hours', 'hybrid'])->default('time');
            $table->unsignedInteger('interval_days')->nullable()->comment('Days between services (time/hybrid)');
            $table->decimal('interval_km', 20, 6)->nullable()->comment('Km between services (odometer/hybrid)');
            $table->decimal('interval_hours', 20, 6)->nullable()->comment('Engine hours between services (engine_hours/hybrid)');
            $table->unsignedInteger('advance_notice_days')->default(7)->comment('Days before due to show warning');

            // Tracking
            $table->timestamp('last_serviced_at')->nullable();
            $table->timestamp('next_service_due_at')->nullable();
            $table->decimal('last_service_odometer', 20, 6)->nullable();
            $table->decimal('next_service_odometer', 20, 6)->nullable();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees', 'id', 'smp_employee_id_fk')->nullOnDelete();

            $table->enum('status', ['active', 'inactive', 'overdue'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'plan_code'], 'smp_tenant_org_code_uk');
            $table->index(['tenant_id', 'asset_id', 'status'], 'smp_tenant_asset_status_idx');
            $table->index(['tenant_id', 'status', 'next_service_due_at'], 'smp_tenant_status_due_idx');
        });

        Schema::create('service_job_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('job_number');
            $table->foreignId('asset_id')->constrained('assets', 'id', 'sjc_asset_id_fk')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id', 'sjc_customer_id_fk')->nullOnDelete()
                ->comment('Set when the service is billable to a customer');
            $table->foreignId('maintenance_plan_id')->nullable()
                ->constrained('service_maintenance_plans', 'id', 'sjc_plan_id_fk')->nullOnDelete()
                ->comment('Links to the PM plan that triggered this job');

            $table->enum('service_type', ['preventive', 'corrective', 'warranty', 'inspection', 'emergency'])->default('preventive');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['draft', 'open', 'in_progress', 'awaiting_parts', 'awaiting_approval', 'completed', 'invoiced', 'cancelled'])
                ->default('draft');

            // Timeline
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Odometer
            $table->decimal('odometer_in', 20, 6)->nullable();
            $table->decimal('odometer_out', 20, 6)->nullable();

            // Financial totals
            $table->boolean('is_billable')->default(false);
            $table->decimal('labour_total', 20, 6)->default('0.000000');
            $table->decimal('parts_total', 20, 6)->default('0.000000');
            $table->decimal('other_charges', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('total_amount', 20, 6)->default('0.000000');

            // Finance links
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'sjc_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('ar_transaction_id')->nullable()->constrained('ar_transactions', 'id', 'sjc_ar_transaction_id_fk')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('employees', 'id', 'sjc_assigned_to_fk')->nullOnDelete();

            // Diagnosis / work detail
            $table->text('diagnosis')->nullable();
            $table->text('work_performed')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'job_number'], 'sjc_tenant_org_number_uk');
            $table->index(['tenant_id', 'asset_id', 'status'], 'sjc_tenant_asset_status_idx');
            $table->index(['tenant_id', 'status', 'scheduled_at'], 'sjc_tenant_status_scheduled_idx');
            $table->index(['tenant_id', 'customer_id', 'status'], 'sjc_tenant_customer_status_idx');
        });

        Schema::create('service_job_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('service_job_card_id')->constrained('service_job_cards', 'id', 'sjt_job_card_id_fk')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('task_code')->nullable();
            $table->string('task_name');
            $table->enum('task_type', ['diagnosis', 'repair', 'replacement', 'inspection', 'cleaning', 'other'])->default('repair');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees', 'id', 'sjt_employee_id_fk')->nullOnDelete();
            $table->decimal('labour_hours', 10, 4)->default('0.0000');
            $table->decimal('labour_rate', 20, 6)->default('0.000000');
            $table->decimal('line_amount', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'service_job_card_id', 'sequence'], 'sjt_tenant_org_job_seq_uk');
            $table->index(['tenant_id', 'assigned_employee_id', 'status'], 'sjt_tenant_employee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_tasks');
        Schema::dropIfExists('service_job_cards');
        Schema::dropIfExists('service_maintenance_plans');
    }
};
