<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustment_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('adjustment_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('location_id')->nullable();
            $table->decimal('quantity_system', 15, 4)->default(0);
            $table->decimal('quantity_counted', 15, 4)->default(0);
            $table->decimal('quantity_difference', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->string('unit_of_measure', 30)->default('piece');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['adjustment_id']);
            $table->index(['product_id']);

            $table->foreign('adjustment_id')->references('id')->on('inventory_adjustments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_lines');
    }
};
