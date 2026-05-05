<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('purchase_return_id')->constrained(null, 'id', 'purchase_return_lines_purchase_return_id_fk')->cascadeOnDelete();
            $table->foreignId('original_grn_line_id')->nullable()->constrained('grn_lines', 'id', 'purchase_return_lines_original_grn_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('from_location_id');
            $table->foreignId('uom_id');
            $table->decimal('return_qty', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('line_cost', 20, 6)->storedAs('return_qty * unit_cost');
            $table->enum('condition', ['good', 'damaged', 'expired', 'defective'])->default('good');
            $table->enum('disposition', ['restock', 'scrap', 'return_to_vendor'])->default('return_to_vendor');
            $table->decimal('restocking_fee', 20, 6)->default(0);
            $table->text('quality_check_notes')->nullable();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serials')->nullOnDelete();
            $table->foreign('from_location_id')->references('id')->on('warehouse_locations')->cascadeOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
    }
};
