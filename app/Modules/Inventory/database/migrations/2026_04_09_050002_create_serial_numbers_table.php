<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->string('serial_number', 150);
            $table->enum('status', ['in_stock', 'sold', 'returned', 'defective', 'disposed']);
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->unique(['tenant_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_numbers');
    }
};
