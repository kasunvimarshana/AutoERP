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
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('priority')->default('medium')->comment('low, medium, high, critical');
            $table->string('status')->default('open')->comment('open, in_progress, waiting_parts, completed, invoiced, cancelled');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 4)->default(1);
            $table->text('reported_issue')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('completed_datetime')->nullable();
            $table->decimal('estimated_hours', 20, 4)->nullable();
            $table->decimal('actual_hours', 20, 4)->nullable();
            $table->dateTime('promised_delivery_date_time')->nullable();
            $table->boolean('warranty_eligible')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreignId('price_list_id')->nullable();
            $table->unsignedBigInteger('start_odometer')->nullable();
            $table->unsignedBigInteger('end_odometer')->nullable();
            $table->unsignedBigInteger('next_service_odometer')->nullable();
            $table->date('next_service_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('employees', 'id')->nullOnDelete()->comment('supervisor assigned to the job card');

            // ── Line‑derived totals – strictly SUM over lines ──
            $table->decimal('subtotal', 20, 4)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 4)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 4)->default(0)->comment('SUM(line.discount_amount)');

            $table->decimal('non_inventory_item_subtotal', 20, 4)->default(0)->comment('SUM(non_inventory_item.gross_amount)');
            $table->decimal('non_inventory_item_tax_total', 20, 4)->default(0)->comment('SUM(non_inventory_item.tax_amount)');
            $table->decimal('non_inventory_item_discount_total', 20, 4)->default(0)->comment('SUM(non_inventory_item.discount_amount)');

            $table->decimal('labour_item_subtotal', 20, 4)->default(0)->comment('SUM(labour_item.gross_amount)');
            $table->decimal('labour_item_tax_total', 20, 4)->default(0)->comment('SUM(labour_item.tax_amount)');
            $table->decimal('labour_item_discount_total', 20, 4)->default(0)->comment('SUM(labour_item.discount_amount)');

            // ── Header‑level adjustments (applied on top of the order) ──
            $table->string('header_discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('header_discount_value', 20, 4)->nullable();
            $table->decimal('header_discount_amount', 20, 4)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 4)->default(0);

            // ── Final totals (combine line + header) ──
            $table->decimal('discount_total', 20, 4)->default(0)->comment('line_discount_total + non_inventory_item_discount_total + labour_item_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 4)->default(0)->comment('line_tax_total + non_inventory_item_tax_total + labour_item_tax_total + header_tax_amount');
            $table->decimal('debit_note_total', 20, 4)->default(0)->comment('SUM of debit notes');
            $table->decimal('credit_note_total', 20, 4)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 4)->default(0)->comment('subtotal - discount_total + tax_total + credit_note_total - debit_note_total');

            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('balance', 20, 4)->default(0)->comment('grand_total - paid_amount');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'job_card_number'], 'vehicle_service_job_cards_job_card_number_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_cards');
    }
};
