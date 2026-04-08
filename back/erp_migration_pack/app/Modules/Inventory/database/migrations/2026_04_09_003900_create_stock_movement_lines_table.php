<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->decimal('quantity', 24, 8);
            $table->decimal('unit_cost', 19, 4)->nullable();
            $table->decimal('total_cost', 19, 4)->nullable();
            $table->string('line_action')->default("move");
            $table->string('source_document_line_type')->nullable();
            $table->unsignedBigInteger('source_document_line_id')->nullable();
            $table->json('metadata')->nullable();
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['stock_movement_id', 'product_variant_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_lines');
    }
};
