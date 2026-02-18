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
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->string('serial_number')->unique();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->string('status')->default('in_stock');
            $table->uuid('customer_id')->nullable();
            $table->uuid('sale_order_id')->nullable();
            $table->date('sale_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->decimal('purchase_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');

            $table->foreign('batch_id')
                ->references('id')
                ->on('batches')
                ->onDelete('set null');

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('set null');

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('set null');

            // Indexes
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'serial_number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index('warranty_end_date');
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
