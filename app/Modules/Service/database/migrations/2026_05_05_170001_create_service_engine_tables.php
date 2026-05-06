<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_work_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('job_card_number');
            $table->foreignId('asset_id')->constrained('assets', 'id', 'service_work_orders_asset_id_fk')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id', 'service_work_orders_customer_id_fk')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users', 'id', 'service_work_orders_opened_by_fk')->nullOnDelete();
            $table->foreignId('assigned_team_org_unit_id')->nullable()->constrained('org_units', 'id', 'service_work_orders_assigned_team_org_unit_id_fk')->nullOnDelete();
            $table->enum('service_type', ['preventive', 'corrective', 'inspection', 'warranty', 'internal']);
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->enum('status', ['draft', 'open', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('meter_in', 20, 6)->nullable();
            $table->decimal('meter_out', 20, 6)->nullable();
            $table->string('meter_unit', 12)->default('km');
            $table->text('symptoms')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('resolution')->nullable();
            $table->enum('billing_mode', ['customer_billable', 'warranty', 'internal_cost', 'rental_intercompany'])->default('customer_billable');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'service_work_orders_currency_id_fk');
            $table->decimal('labor_subtotal', 20, 6)->default(0);
            $table->decimal('parts_subtotal', 20, 6)->default(0);
            $table->decimal('other_subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'service_work_orders_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'job_card_number'], 'service_work_orders_tenant_job_card_uk');
            $table->index(['tenant_id', 'asset_id', 'status'], 'service_work_orders_asset_status_idx');
            $table->index(['tenant_id', 'status', 'scheduled_start_at'], 'service_work_orders_status_schedule_idx');
        });

        Schema::create('service_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('service_work_order_id')->constrained('service_work_orders', 'id', 'service_tasks_work_order_id_fk')->cascadeOnDelete();
            $table->unsignedInteger('line_number')->default(1);
            $table->string('task_code')->nullable();
            $table->text('description');
            $table->decimal('estimated_hours', 20, 6)->default(0);
            $table->decimal('actual_hours', 20, 6)->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees', 'id', 'service_tasks_assigned_employee_id_fk')->nullOnDelete();
            $table->decimal('labor_rate', 20, 6)->default(0);
            $table->decimal('labor_amount', 20, 6)->default(0);
            $table->decimal('commission_amount', 20, 6)->default(0);
            $table->decimal('incentive_amount', 20, 6)->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'service_work_order_id', 'line_number'], 'service_tasks_work_order_line_uk');
            $table->index(['tenant_id', 'assigned_employee_id', 'status'], 'service_tasks_employee_status_idx');
        });

        Schema::create('service_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('service_work_order_id')->constrained('service_work_orders', 'id', 'service_parts_work_order_id_fk')->cascadeOnDelete();
            $table->foreignId('service_task_id')->nullable()->constrained('service_tasks', 'id', 'service_parts_task_id_fk')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'service_parts_product_id_fk')->nullOnDelete();
            $table->enum('part_source', ['inventory', 'non_inventory', 'special_order'])->default('inventory');
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 6)->default(0);
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure', 'id', 'service_parts_uom_id_fk')->nullOnDelete();
            $table->decimal('unit_cost', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('line_amount', 20, 6)->default(0);
            $table->boolean('is_returned')->default(false);
            $table->boolean('is_warranty_covered')->default(false);
            $table->nullableMorphs('stock_reference');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'service_work_order_id'], 'service_parts_work_order_idx');
            $table->index(['tenant_id', 'part_source', 'product_id'], 'service_parts_source_product_idx');
        });

        Schema::create('service_labor_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('service_work_order_id')->constrained('service_work_orders', 'id', 'service_labor_entries_work_order_id_fk')->cascadeOnDelete();
            $table->foreignId('service_task_id')->nullable()->constrained('service_tasks', 'id', 'service_labor_entries_task_id_fk')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees', 'id', 'service_labor_entries_employee_id_fk')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->decimal('hours_worked', 20, 6)->default(0);
            $table->decimal('labor_rate', 20, 6)->default(0);
            $table->decimal('labor_amount', 20, 6)->default(0);
            $table->decimal('commission_rate', 20, 6)->default(0);
            $table->decimal('commission_amount', 20, 6)->default(0);
            $table->decimal('incentive_amount', 20, 6)->default(0);
            $table->enum('status', ['draft', 'approved', 'posted'])->default('draft');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'started_at'], 'service_labor_entries_employee_date_idx');
            $table->index(['tenant_id', 'service_work_order_id', 'status'], 'service_labor_entries_work_order_status_idx');
        });

        Schema::create('service_warranty_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('service_work_order_id')->constrained('service_work_orders', 'id', 'service_warranty_claims_work_order_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id', 'service_warranty_claims_supplier_id_fk')->nullOnDelete();
            $table->string('warranty_provider')->nullable();
            $table->string('claim_number')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'settled'])->default('draft');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'service_warranty_claims_currency_id_fk');
            $table->decimal('claim_amount', 20, 6)->default(0);
            $table->decimal('approved_amount', 20, 6)->default(0);
            $table->decimal('received_amount', 20, 6)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'service_warranty_claims_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'submitted_at'], 'service_warranty_claims_status_submitted_idx');
            $table->unique(['tenant_id', 'org_unit_id', 'claim_number'], 'service_warranty_claims_claim_number_uk');
        });

        Schema::create('service_returns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('service_work_order_id')->constrained('service_work_orders', 'id', 'service_returns_work_order_id_fk')->cascadeOnDelete();
            $table->string('return_number');
            $table->enum('return_type', ['inventory_return', 'customer_refund', 'supplier_return']);
            $table->enum('status', ['draft', 'approved', 'completed', 'cancelled'])->default('draft');
            $table->string('reason_code')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users', 'id', 'service_returns_processed_by_fk')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'service_returns_currency_id_fk');
            $table->decimal('total_amount', 20, 6)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'service_returns_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'service_returns_payment_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'return_number'], 'service_returns_tenant_return_number_uk');
            $table->index(['tenant_id', 'status', 'processed_at'], 'service_returns_status_processed_idx');
        });

        Schema::create('service_return_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('service_return_id')->constrained('service_returns', 'id', 'service_return_lines_return_id_fk')->cascadeOnDelete();
            $table->foreignId('service_part_id')->nullable()->constrained('service_parts', 'id', 'service_return_lines_part_id_fk')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'service_return_lines_product_id_fk')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 6)->default(0);
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure', 'id', 'service_return_lines_uom_id_fk')->nullOnDelete();
            $table->decimal('unit_amount', 20, 6)->default(0);
            $table->decimal('line_amount', 20, 6)->default(0);
            $table->nullableMorphs('stock_reference');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'service_return_id'], 'service_return_lines_return_idx');
        });

        Schema::create('intercompany_billings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('billing_number');
            $table->foreignId('from_org_unit_id')->constrained('org_units', 'id', 'intercompany_billings_from_org_unit_id_fk')->cascadeOnDelete();
            $table->foreignId('to_org_unit_id')->constrained('org_units', 'id', 'intercompany_billings_to_org_unit_id_fk')->cascadeOnDelete();
            $table->nullableMorphs('source');
            $table->enum('billing_type', ['service_to_rental', 'rental_to_service', 'shared_cost']);
            $table->enum('status', ['draft', 'posted', 'settled', 'cancelled'])->default('draft');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'intercompany_billings_currency_id_fk');
            $table->decimal('amount', 20, 6);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('total_amount', 20, 6);
            $table->timestamp('billed_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('from_journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'intercompany_billings_from_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('to_journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'intercompany_billings_to_journal_entry_id_fk')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'intercompany_billings_payment_id_fk')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('intercompany_billings', 'id', 'intercompany_billings_reversal_of_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'billing_number'], 'intercompany_billings_tenant_number_uk');
            $table->index(['tenant_id', 'status', 'due_at'], 'intercompany_billings_status_due_idx');
            $table->index(['tenant_id', 'from_org_unit_id', 'to_org_unit_id'], 'intercompany_billings_org_pair_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'intercompany_billings_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intercompany_billings');
        Schema::dropIfExists('service_return_lines');
        Schema::dropIfExists('service_returns');
        Schema::dropIfExists('service_warranty_claims');
        Schema::dropIfExists('service_labor_entries');
        Schema::dropIfExists('service_parts');
        Schema::dropIfExists('service_tasks');
        Schema::dropIfExists('service_work_orders');
    }
};
