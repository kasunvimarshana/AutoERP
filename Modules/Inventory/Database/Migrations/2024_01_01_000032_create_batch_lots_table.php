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
        Schema::create('batch_lots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->string('batch_number', 100);
            $table->ulid('product_id');
            $table->ulid('warehouse_id');
            $table->ulid('location_id')->nullable();
            $table->decimal('quantity', 15, 6)->default(0);
            $table->decimal('reserved_quantity', 15, 6)->default(0);
            $table->decimal('available_quantity', 15, 6)->default(0);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('supplier_batch_number', 100)->nullable();
            $table->decimal('cost', 15, 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'batch_number']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'location_id']);
            $table->index(['tenant_id', 'expiry_date']);
            $table->index(['tenant_id', 'is_active']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'product_id', 'warehouse_id']);
            $table->index(['tenant_id', 'product_id', 'batch_number']);
            $table->index(['tenant_id', 'product_id', 'expiry_date']);
            $table->index(['tenant_id', 'warehouse_id', 'expiry_date']);
            $table->index(['tenant_id', 'product_id', 'is_active', 'available_quantity'], 'idx_batch_availability');

            // Unique constraint for batch number per product per warehouse
            $table->unique(['tenant_id', 'product_id', 'warehouse_id', 'batch_number'], 'unique_batch_lot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_lots');
    }
};
