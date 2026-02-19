<?php

declare(strict_types=1);

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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->string('type', 20);
            $table->ulid('product_id');
            $table->ulid('from_warehouse_id')->nullable();
            $table->ulid('to_warehouse_id')->nullable();
            $table->ulid('from_location_id')->nullable();
            $table->ulid('to_location_id')->nullable();
            $table->decimal('quantity', 15, 6);
            $table->decimal('cost', 15, 6)->nullable();
            $table->string('reference_type', 255)->nullable();
            $table->ulid('reference_id')->nullable();
            $table->ulid('batch_lot_id')->nullable();
            $table->ulid('serial_number_id')->nullable();
            $table->timestamp('movement_date')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'from_warehouse_id']);
            $table->index(['tenant_id', 'to_warehouse_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'movement_date']);
            $table->index(['tenant_id', 'reference_type', 'reference_id']);
            $table->index(['tenant_id', 'batch_lot_id']);
            $table->index(['tenant_id', 'serial_number_id']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'product_id', 'type']);
            $table->index(['tenant_id', 'product_id', 'from_warehouse_id']);
            $table->index(['tenant_id', 'product_id', 'to_warehouse_id']);
            $table->index(['tenant_id', 'product_id', 'movement_date']);
            $table->index(['tenant_id', 'type', 'movement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
