<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('location_id');
            $table->string('movement_type', 50);
            $table->decimal('quantity', 15, 4);
            $table->string('reference_number', 100);
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('unit_cost', 15, 6)->nullable();
            $table->unsignedBigInteger('related_movement_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('moved_at')->nullable();
            $table->unsignedBigInteger('moved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('reference_number');
            $table->index(['tenant_id', 'product_id']);
            $table->index('movement_type');
            $table->foreign('related_movement_id')->references('id')->on('stock_movements')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
