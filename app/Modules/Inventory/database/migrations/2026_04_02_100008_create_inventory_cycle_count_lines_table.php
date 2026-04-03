<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('cycle_count_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('expected_qty', 15, 4)->default(0);
            $table->decimal('counted_qty', 15, 4)->nullable();
            $table->decimal('variance_qty', 15, 4)->default(0);
            $table->string('status', 50)->default('pending');
            $table->timestamp('counted_at')->nullable();
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cycle_count_id')->references('id')->on('inventory_cycle_counts')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
            $table->foreign('serial_number_id')->references('id')->on('inventory_serial_numbers')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('inventory_locations')->nullOnDelete();

            $table->index('tenant_id');
            $table->index('cycle_count_id');
            $table->index('product_id');
            $table->index('location_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cycle_count_lines');
    }
};
