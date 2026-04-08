<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_line_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('commercial_document_line_id')->constrained('commercial_document_lines')->cascadeOnDelete();
            $table->foreignId('stock_movement_line_id')->nullable()->constrained('stock_movement_lines')->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('units_of_measure')->cascadeOnDelete();
            $table->decimal('quantity', 24, 8);
            $table->decimal('unit_cost', 19, 4)->nullable();
            $table->decimal('unit_price', 19, 4)->nullable();
            $table->string('allocation_status')->default("allocated");
            $table->json('metadata')->nullable();
            $table->index(['tenant_id', 'commercial_document_line_id']);
            $table->index(['tenant_id', 'lot_id']);
            $table->index(['tenant_id', 'serial_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_line_allocations');
    }
};
