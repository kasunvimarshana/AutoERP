<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('cycle_count_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('batch_lot_id')->nullable();
            $table->decimal('system_quantity', 15, 4)->default(0);
            $table->decimal('counted_quantity', 15, 4)->default(0);
            $table->decimal('variance', 15, 4)->default(0);
            // pending, matched, variance
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cycle_count_id']);
            $table->index(['product_id']);

            $table->foreign('cycle_count_id')
                ->references('id')
                ->on('cycle_counts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
    }
};
