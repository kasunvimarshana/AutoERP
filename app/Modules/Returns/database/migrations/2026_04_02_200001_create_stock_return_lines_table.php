<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('stock_return_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_requested', 15, 4);
            $table->decimal('quantity_approved', 15, 4)->nullable();
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->string('condition', 50)->default('good');
            $table->string('disposition', 50)->default('restock');
            $table->string('quality_check_status', 50)->default('pending');
            $table->unsignedBigInteger('quality_checked_by')->nullable();
            $table->timestamp('quality_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'stock_return_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_return_lines');
    }
};
