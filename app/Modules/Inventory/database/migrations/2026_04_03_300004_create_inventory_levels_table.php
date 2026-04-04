<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('quantity_available', 15, 4)->default(0);
            $table->decimal('quantity_on_order', 15, 4)->default(0);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('serial_id')->nullable();
            $table->string('stock_status')->default('available');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'product_id', 'warehouse_id', 'location_id', 'batch_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_levels');
    }
};
