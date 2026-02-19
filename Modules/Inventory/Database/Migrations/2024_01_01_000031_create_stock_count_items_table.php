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
        Schema::create('stock_count_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('stock_count_id');
            $table->ulid('product_id');
            $table->ulid('location_id')->nullable();
            $table->ulid('batch_lot_id')->nullable();
            $table->decimal('system_quantity', 15, 6)->default(0);
            $table->decimal('counted_quantity', 15, 6)->nullable();
            $table->decimal('variance', 15, 6)->nullable();
            $table->decimal('unit_cost', 15, 6)->nullable();
            $table->decimal('variance_value', 15, 6)->nullable();
            $table->text('notes')->nullable();
            $table->string('counted_by', 255)->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'stock_count_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'location_id']);
            $table->index(['tenant_id', 'batch_lot_id']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'stock_count_id', 'product_id']);
            $table->index(['tenant_id', 'stock_count_id', 'variance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
    }
};
