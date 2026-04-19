<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'transfer_order_lines_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('transfer_order_id')->constrained('transfer_orders', 'id', 'transfer_order_lines_transfer_order_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'transfer_order_lines_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'transfer_order_lines_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'transfer_order_lines_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'transfer_order_lines_serial_id_fk')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations', 'id', 'transfer_order_lines_from_location_id_fk')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations', 'id', 'transfer_order_lines_to_location_id_fk')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'transfer_order_lines_uom_id_fk');
            $table->decimal('requested_qty', 20, 6);
            $table->decimal('shipped_qty', 20, 6)->default(0);
            $table->decimal('received_qty', 20, 6)->default(0);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('line_cost', 20, 6)->storedAs('requested_qty * unit_cost');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_order_lines');
    }
};
