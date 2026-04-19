<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id', 'cycle_count_lines_tenant_id_fk')->nullOnDelete();
            $table->foreignId('count_header_id')->constrained('cycle_count_headers', 'id', 'cycle_count_lines_count_header_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'cycle_count_lines_product_id_fk');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'cycle_count_lines_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'cycle_count_lines_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'cycle_count_lines_serial_id_fk')->nullOnDelete();
            $table->decimal('system_qty', 20, 6);
            $table->decimal('counted_qty', 20, 6);
            $table->decimal('variance_qty', 20, 6);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('variance_value', 20, 6);
            $table->foreignId('adjustment_movement_id')->nullable()->constrained('stock_movements', 'id', 'cycle_count_lines_adjustment_movement_id_fk')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
    }
};
