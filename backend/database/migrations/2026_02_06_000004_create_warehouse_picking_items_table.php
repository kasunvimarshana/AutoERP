<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_picking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_picking_id')->constrained('warehouse_pickings')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            
            // Quantity Information
            $table->decimal('quantity_required', 15, 4);
            $table->decimal('quantity_picked', 15, 4)->default(0);
            $table->foreignId('unit_id')->constrained('units_of_measure');
            
            // Batch/Serial/Lot Tracking
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            
            // Picking Order
            $table->integer('sequence')->default(0);
            
            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'short'])->default('pending');
            
            // Notes
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['warehouse_picking_id', 'status']);
            $table->index(['product_id', 'location_id']);
            $table->index(['batch_number', 'serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_picking_items');
    }
};
