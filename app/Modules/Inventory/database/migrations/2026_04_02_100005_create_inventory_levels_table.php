<?php

declare(strict_types=1);

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
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('qty_on_hand', 15, 4)->default(0);
            $table->decimal('qty_reserved', 15, 4)->default(0);
            $table->decimal('qty_available', 15, 4)->default(0);
            $table->decimal('qty_on_order', 15, 4)->default(0);
            $table->decimal('reorder_point', 15, 4)->nullable();
            $table->decimal('reorder_qty', 15, 4)->nullable();
            $table->decimal('max_qty', 15, 4)->nullable();
            $table->decimal('min_qty', 15, 4)->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('location_id')->references('id')->on('inventory_locations')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('location_id');
            $table->index('batch_id');
            $table->index(['tenant_id', 'product_id', 'variation_id', 'location_id', 'batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_levels');
    }
};
