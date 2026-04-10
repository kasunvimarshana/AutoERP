<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_count_id')->constrained('cycle_counts')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->decimal('expected_qty', 18, 4);
            $table->decimal('counted_qty', 18, 4)->nullable();
            $table->decimal('variance_qty', 18, 4)->nullable();
            $table->enum('status', ['pending', 'counted', 'adjusted'])->default('pending');
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
    }
};
