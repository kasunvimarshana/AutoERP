<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('stock_transfer_id')->constrained('stock_transfers', 'id', 'stock_transfer_lines_stock_transfer_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'stock_transfer_lines_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_transfer_lines_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'stock_transfer_lines_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'stock_transfer_lines_serial_id_fk')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'stock_transfer_lines_uom_id_fk');
            $table->decimal('quantity', 20, 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
    }
};
