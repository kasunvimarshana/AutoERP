<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_stock_adjustment_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('adjustment_id');
            $table->uuid('product_id');
            $table->uuid('variation_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->string('lot_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('adjustment_id')->references('id')->on('pos_stock_adjustments')->onDelete('cascade');
            $table->index(['tenant_id', 'adjustment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_adjustment_lines');
    }
};
