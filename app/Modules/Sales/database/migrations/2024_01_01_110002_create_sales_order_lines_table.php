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

            $table->foreignId('sales_order_id')
                ->constrained('sales_orders', 'id', 'sales_order_lines_sales_order_id_fk')
                ->cascadeOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('ordered_qty', 20, 6);
            $table->decimal('shipped_qty', 20, 6)->default(0);
            $table->decimal('reserved_qty', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_pct', 10, 6)->default(0);
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('line_total', 20, 6);
            // Sales order lines income account
            $table->foreignId('income_account_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();
            $table->foreign('income_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serials')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
