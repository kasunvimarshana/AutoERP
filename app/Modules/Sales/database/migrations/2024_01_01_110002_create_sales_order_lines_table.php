<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('sales_order_id')->constrained('sales_orders', 'id', 'sales_order_lines_sales_order_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('warehouse_id')->nullable();
            $table->foreignId('location_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('ordered_qty', 20, 6);
            $table->decimal('shipped_qty', 20, 6)->default(0);
            $table->decimal('reserved_qty', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6);

            // Discount – stored both as configuration and as absolute amount
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 6)
                  ->storedAs('ordered_qty * unit_price')
                  ->comment('Gross = qty * unit price');
            $table->decimal('line_total', 20, 6)
                  ->storedAs('gross_amount - discount_amount')
                  ->comment('Net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('tax_amount', 20, 6)->default(0)
                  ->comment('Calculated tax amount');

            // Optional – if you want a stored line total including tax
            $table->decimal('line_total_with_tax', 20, 6)
                  ->storedAs('line_total + tax_amount')
                  ->comment('total including tax');

            // Sales order lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id', 'sales_order_lines_account_id_fk')->nullOnDelete()->comment('account for posting');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serials')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('warehouse_locations')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
