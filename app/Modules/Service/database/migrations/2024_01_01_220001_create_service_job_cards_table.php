<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_cards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('service_type_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('vehicle_id')->nullable();
            $table->foreignId('warehouse_id');
            $table->string('job_card_number');
            $table->enum('priority', ['low','medium','high','critical'])->default('medium');
            $table->enum('status', ['open','in_progress','waiting_parts','completed','invoiced','cancelled'])->default('open');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'sales_orders_currency_id_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->text('reported_issue')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('completed_datetime')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->foreignId('price_list_id')->nullable();

            // ── Line‑derived totals – strictly SUM over lines ──
            $table->decimal('line_subtotal', 20, 6)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 6)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 6)->default(0)->comment('SUM(line.discount_amount)');

            $table->decimal('non_inventory_item_subtotal', 20, 6)->default(0)->comment('SUM(non_inventory_item.gross_amount)');
            $table->decimal('non_inventory_item_tax_total', 20, 6)->default(0)->comment('SUM(non_inventory_item.tax_amount)');
            $table->decimal('non_inventory_item_discount_total', 20, 6)->default(0)->comment('SUM(non_inventory_item.discount_amount)');

            $table->decimal('labour_item_subtotal', 20, 6)->default(0)->comment('SUM(labour_item.gross_amount)');
            $table->decimal('labour_item_tax_total', 20, 6)->default(0)->comment('SUM(labour_item.tax_amount)');
            $table->decimal('labour_item_discount_total', 20, 6)->default(0)->comment('SUM(labour_item.discount_amount)');

            // ── Header‑level adjustments (applied on top of the order) ──
            $table->enum('header_discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('header_discount_value', 10, 6)->nullable();
            $table->decimal('header_discount_amount', 20, 6)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 6)->default(0);

            // ── Final totals (combine line + header) ──
            $table->decimal('discount_total', 20, 6)->default(0)->comment('line_discount_total + non_inventory_item_discount_total + labour_item_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 6)->default(0)->comment('line_tax_total + non_inventory_item_tax_total + labour_item_tax_total + header_tax_amount');
            $table->decimal('surcharge_total', 20, 6)->default(0)->comment('SUM of surcharge notes');
            $table->decimal('credit_total', 20, 6)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 6)->default(0)->comment('subtotal - discount_total + tax_total + surcharge_total - credit_total');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('warranty_eligible')->default(false);
            $table->foreignId('created_by');
            $table->foreignId('assigned_to')->nullable(); // supervisor assigned to the job card

            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->nullable()->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullable()->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('employees')->nullable()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->text('diagnostic_notes')->nullable();

            $table->unique(['tenant_id','org_unit_id','job_card_number'], 'job_cards_number_uk');
            $table->index(['tenant_id','status'], 'job_cards_status_idx');
            $table->index(['vehicle_id','start_datetime'], 'job_cards_vehicle_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_job_cards');
    }
};
