<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('job_card_number');
            $table->string('reference')->nullable();
            $table->foreignId('service_type_id')->nullable()->constrained('vehicle_service_types')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->restrictOnDelete();
            $table->unsignedBigInteger('vehicle_ownership_id')->nullable()->comment('Current ownership snapshot/reference from Vehicle at intake');
            $table->string('vehicle_owner_type')->nullable()->comment('company, customer, supplier, employee, partner, external_party, party, other');
            $table->unsignedBigInteger('vehicle_owner_id')->nullable();
            $table->string('vehicle_owner_name')->nullable();
            $table->string('service_customer_type')->nullable()->comment('customer, supplier_as_customer, party, external_party, internal_company');
            $table->unsignedBigInteger('service_customer_id')->nullable();
            $table->string('service_customer_name')->nullable();
            $table->foreignId('linked_customer_id')->nullable()->constrained('customers')->restrictOnDelete()->comment('Customer role used for invoicing when available');
            $table->string('billing_customer_type')->nullable()->comment('customer, supplier_as_customer, party, external_party, internal_company, insurance_company');
            $table->unsignedBigInteger('billing_customer_id')->nullable();
            $table->string('billing_customer_name')->nullable();
            $table->string('payer_type')->nullable()->comment('customer, supplier, party, external_party, internal_company, insurance_company');
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->string('payer_name')->nullable();
            $table->json('party_context')->nullable()->comment('Readonly source/role context and backend validation warnings');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->string('priority')->default('medium')->comment('low, medium, high, critical');
            $table->string('status')->default('open')->comment('open, in_progress, waiting_parts, completed, invoiced, cancelled');
            $table->string('inventory_status')->default('pending')->comment('pending, reserved, consumed, partially_consumed, reversed');
            $table->string('invoice_status')->default('pending')->comment('pending, partially_invoiced, invoiced, reversed');
            $table->string('payment_status')->default('unpaid')->comment('unpaid, partially_paid, paid, overpaid, refunded');
            $table->string('finance_status')->default('draft')->comment('draft, posted, reversed');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->text('reported_issue')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->text('technician_notes')->nullable();
            $table->text('customer_feedback')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('completed_datetime')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('ready_for_delivery_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->decimal('estimated_hours', 20, 4)->nullable();
            $table->decimal('actual_hours', 20, 4)->nullable();
            $table->dateTime('promised_delivery_date_time')->nullable();
            $table->boolean('warranty_eligible')->default(false);
            $table->boolean('is_customer_approval_required')->default(false);
            $table->boolean('is_customer_approved')->default(false);
            $table->dateTime('customer_approved_at')->nullable();
            $table->unsignedBigInteger('customer_approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();
            $table->unsignedBigInteger('next_service_odometer')->nullable();
            $table->date('next_service_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('employees', 'id')->nullOnDelete()->comment('supervisor assigned to the job card');

            // Line-derived totals - strictly SUM over lines
            $table->decimal('subtotal', 20, 4)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 4)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 4)->default(0)->comment('SUM(line.discount_amount)');

            $table->decimal('non_inventory_item_subtotal', 20, 4)->default(0)->comment('SUM(non_inventory_item.gross_amount)');
            $table->decimal('non_inventory_item_tax_total', 20, 4)->default(0)->comment('SUM(non_inventory_item.tax_amount)');
            $table->decimal('non_inventory_item_discount_total', 20, 4)->default(0)->comment('SUM(non_inventory_item.discount_amount)');

            $table->decimal('labor_item_subtotal', 20, 4)->default(0)->comment('SUM(labor_item.gross_amount)');
            $table->decimal('labor_item_tax_total', 20, 4)->default(0)->comment('SUM(labor_item.tax_amount)');
            $table->decimal('labor_item_discount_total', 20, 4)->default(0)->comment('SUM(labor_item.discount_amount)');

            // Header-level adjustments applied on top of the document
            $table->string('header_discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('header_discount_value', 20, 4)->nullable();
            $table->decimal('header_discount_amount', 20, 4)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 4)->default(0);

            // Final totals combine line rollups and header adjustments
            $table->decimal('discount_total', 20, 4)->default(0)->comment('Application-calculated: line_discount_total + non_inventory_item_discount_total + labor_item_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 4)->default(0)->comment('Application-calculated: line_tax_total + non_inventory_item_tax_total + labor_item_tax_total + header_tax_amount');
            $table->decimal('charge_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0)->comment('Application-calculated: all subtotals - discount_total + tax_total + charge_total');

            $table->decimal('advance_amount', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('refund_amount', 20, 4)->default(0);
            $table->decimal('write_off_amount', 20, 4)->default(0);
            $table->decimal('balance', 20, 4)->default(0)->comment('Application-calculated: grand_total - paid_amount');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'job_card_number'], 'vehicle_service_job_cards_job_card_number_uk');
            $table->index(['tenant_id', 'status'], 'vehicle_service_job_cards_status_idx');
            $table->index(['tenant_id', 'invoice_status'], 'vehicle_service_job_cards_invoice_status_idx');
            $table->index(['tenant_id', 'payment_status'], 'vehicle_service_job_cards_payment_status_idx');
            $table->index(['tenant_id', 'vehicle_id'], 'vehicle_service_job_cards_vehicle_idx');
            $table->index(['tenant_id', 'linked_customer_id'], 'vehicle_service_job_cards_customer_role_idx');
            $table->index(['tenant_id', 'service_customer_type', 'service_customer_id'], 'vehicle_service_job_cards_service_party_idx');
            $table->index(['tenant_id', 'billing_customer_type', 'billing_customer_id'], 'vehicle_service_job_cards_billing_party_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_cards');
    }
};
