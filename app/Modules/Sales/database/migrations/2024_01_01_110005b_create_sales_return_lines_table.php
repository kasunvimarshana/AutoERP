<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('sales_return_id')
                ->constrained('sales_returns', 'id', 'sales_return_lines_sales_return_id_fk')
                ->cascadeOnDelete();
            $table->foreignId('original_sales_order_line_id')->nullable()->constrained('sales_order_lines', 'id', 'sales_return_lines_original_sales_order_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('to_location_id');
            $table->foreignId('uom_id');
            $table->decimal('return_qty', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('line_total', 20, 6)->storedAs('return_qty * unit_price');
            $table->enum('condition', ['good', 'damaged', 'expired', 'defective'])->default('good');
            $table->enum('disposition', ['restock', 'scrap', 'quarantine'])->default('restock');
            $table->decimal('restocking_fee', 20, 6)->default(0);
            $table->text('quality_check_notes')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serials')->nullOnDelete();
            $table->foreign('to_location_id')->references('id')->on('warehouse_locations')->cascadeOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_lines');
    }
};
