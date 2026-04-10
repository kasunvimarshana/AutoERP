<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->decimal('qty_on_hand', 18, 4)->default(0);
            $table->decimal('qty_reserved', 18, 4)->default(0);
            $table->decimal('qty_available', 18, 4)->default(0);
            $table->decimal('qty_incoming', 18, 4)->default(0);
            $table->decimal('avg_cost', 18, 4)->default(0);
            $table->timestamp('updated_at')->useCurrent();
            $table->unique(['tenant_id', 'product_id', 'variant_id', 'batch_id', 'location_id', 'uom_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
