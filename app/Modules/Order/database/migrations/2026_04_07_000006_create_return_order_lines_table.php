<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('return_order_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('product_name', 300)->nullable();
            $table->string('unit_of_measure', 30)->default('piece');
            $table->decimal('quantity_returned', 15, 4);
            $table->decimal('quantity_restocked', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4)->default(0);
            // good, damaged, expired, defective
            $table->string('condition', 30)->default('good');
            $table->boolean('is_restockable')->default(true);
            // Batch/lot traceability for returned items
            $table->uuid('batch_lot_id')->nullable();
            // pending, passed, failed, quarantine
            $table->string('quality_check_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['return_order_id']);

            $table->foreign('return_order_id')->references('id')->on('return_orders')->cascadeOnDelete();
            $table->foreign('batch_lot_id')->references('id')->on('batch_lots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_order_lines');
    }
};
