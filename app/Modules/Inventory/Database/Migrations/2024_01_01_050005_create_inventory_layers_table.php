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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->date('receipt_date');
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('qty_received', 18, 4);
            $table->decimal('qty_remaining', 18, 4);
            $table->enum('method', ['FIFO', 'LIFO', 'FEFO', 'WAC', 'SPECIFIC']);
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->boolean('is_exhausted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_layers');
    }
};