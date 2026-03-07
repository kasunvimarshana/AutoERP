<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // One product per warehouse per tenant
            $table->unique(['tenant_id', 'product_id', 'warehouse_id'], 'inventory_tenant_product_warehouse_unique');
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'quantity']);

            $table->foreign('warehouse_id')
                  ->references('id')
                  ->on('warehouses')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
