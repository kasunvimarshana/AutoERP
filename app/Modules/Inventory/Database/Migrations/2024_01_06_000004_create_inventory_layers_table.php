<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedBigInteger('location_id');
            $table->date('receipt_date');
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('qty_received', 18, 4);
            $table->decimal('qty_remaining', 18, 4);
            $table->enum('method', ['FIFO', 'LIFO', 'FEFO', 'WAC', 'SPECIFIC']);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->boolean('is_exhausted')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete();

            $table->index(['tenant_id', 'product_id', 'is_exhausted']);
            $table->index(['source_type', 'source_id']);
            $table->index('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_layers');
    }
};