<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cycle_count_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cycle_count_id')->index();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();

            // System-known qty at time of count (DECIMAL(18,8) for BCMath compatibility)
            $table->decimal('expected_qty', 18, 8)->default(0);

            // Physically counted qty
            $table->decimal('counted_qty', 18, 8)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            // One line per product per cycle count
            $table->unique(['cycle_count_id', 'product_id']);

            $table->foreign('cycle_count_id')
                ->references('id')
                ->on('inventory_cycle_counts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cycle_count_lines');
    }
};
