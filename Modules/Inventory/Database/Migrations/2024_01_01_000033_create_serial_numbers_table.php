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
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->string('serial_number', 255);
            $table->ulid('product_id');
            $table->ulid('warehouse_id')->nullable();
            $table->ulid('location_id')->nullable();
            $table->string('status', 20)->default('in_stock');
            $table->ulid('batch_lot_id')->nullable();
            $table->string('reference_type', 255)->nullable();
            $table->ulid('reference_id')->nullable();
            $table->date('received_date')->nullable();
            $table->date('sold_date')->nullable();
            $table->decimal('cost', 15, 6)->nullable();
            $table->integer('warranty_months')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'serial_number']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'location_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'batch_lot_id']);
            $table->index(['tenant_id', 'reference_type', 'reference_id']);
            $table->index(['tenant_id', 'warranty_expiry_date']);

            // Composite indexes for common queries
            $table->index(['tenant_id', 'product_id', 'status']);
            $table->index(['tenant_id', 'product_id', 'warehouse_id']);
            $table->index(['tenant_id', 'product_id', 'serial_number']);
            $table->index(['tenant_id', 'warehouse_id', 'status']);
            $table->index(['tenant_id', 'status', 'warranty_expiry_date']);

            // Unique constraint for serial number per product
            $table->unique(['tenant_id', 'product_id', 'serial_number'], 'unique_serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
