<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('customer_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders', 'id', 'shipments_sales_order_id_fk')->nullOnDelete();
            $table->string('shipment_number');
            $table->string('status')->default('draft')->comment('draft, picking, packed, shipped, delivered, cancelled');
            $table->foreignId('currency_id')->nullable()->constrained('currencies', 'id', 'shipments_currency_id_fk')->nullOnDelete();
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->date('shipped_date')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('price_list_id')->nullable();

            // ── Line‑derived totals – strictly SUM over lines ──
            $table->decimal('subtotal', 20, 6)->default(0)->comment('SUM(line.gross_amount)');
            $table->decimal('line_tax_total', 20, 6)->default(0)->comment('SUM(line.tax_amount)');
            $table->decimal('line_discount_total', 20, 6)->default(0)->comment('SUM(line.discount_amount)');

            // ── Header‑level adjustments (applied on top of the order) ──
            $table->enum('header_discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('header_discount_value', 10, 6)->nullable();
            $table->decimal('header_discount_amount', 20, 6)->default(0);
            $table->foreignId('header_tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('header_tax_amount', 20, 6)->default(0);

            // ── Final totals (combine line + header) ──
            $table->decimal('discount_total', 20, 6)->default(0)->comment('line_discount_total + header_discount_amount');
            $table->decimal('tax_total', 20, 6)->default(0)->comment('line_tax_total + header_tax_amount');
            $table->decimal('surcharge_total', 20, 6)->default(0)->comment('SUM of surcharge notes');
            $table->decimal('credit_total', 20, 6)->default(0)->comment('SUM of credit notes');
            $table->decimal('grand_total', 20, 6)->default(0)->comment('subtotal - discount_total + tax_total + surcharge_total - credit_total');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by');

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->nullable()->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullable()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'shipment_number'], 'shipments_tenant_shipment_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
