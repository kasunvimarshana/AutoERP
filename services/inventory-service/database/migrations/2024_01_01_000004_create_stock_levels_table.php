<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('tenant_id', 64)->index();
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            $table->decimal('quantity_available', 15, 4)->default(0);
            $table->decimal('quantity_reserved',  15, 4)->default(0);
            $table->decimal('quantity_on_hand',   15, 4)->default(0);
            $table->unsignedBigInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id', 'tenant_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'warehouse_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_levels'); }
};
