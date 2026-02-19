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
        Schema::create('stock_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('product_id');
            $table->ulid('warehouse_id');
            $table->ulid('location_id')->nullable();
            $table->decimal('quantity', 15, 6)->default(0);
            $table->decimal('reserved_quantity', 15, 6)->default(0);
            $table->decimal('available_quantity', 15, 6)->default(0);
            $table->decimal('reorder_point', 15, 6)->nullable();
            $table->decimal('reorder_quantity', 15, 6)->nullable();
            $table->decimal('minimum_quantity', 15, 6)->nullable();
            $table->decimal('maximum_quantity', 15, 6)->nullable();
            $table->decimal('average_cost', 15, 6)->default(0);
            $table->date('last_stock_count_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'location_id']);
            $table->index(['tenant_id', 'product_id', 'warehouse_id']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'warehouse_id', 'available_quantity']);
            $table->index(['tenant_id', 'product_id', 'warehouse_id', 'available_quantity'], 'idx_stock_availability');

            // Unique constraint to prevent duplicate stock items
            $table->unique(['tenant_id', 'product_id', 'warehouse_id', 'location_id'], 'unique_stock_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
