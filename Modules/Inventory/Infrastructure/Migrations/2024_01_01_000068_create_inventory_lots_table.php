<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();

            // Human-readable batch / lot / serial number
            $table->string('lot_number', 100);

            // Tracking granularity: lot (many units), serial (one unit)
            $table->string('tracking_type', 10)->default('lot');

            // Quantity covered by this lot (DECIMAL(18,8) for BCMath compatibility)
            $table->decimal('qty', 18, 8)->default(0);

            // active | blocked (blocked lots cannot be dispatched — recall management)
            $table->string('status', 10)->default('active');

            // Optional production / expiry metadata
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Unique lot number per tenant + product combination
            $table->unique(['tenant_id', 'product_id', 'lot_number']);

            // Composite index for efficient per-product lot queries
            $table->index(['tenant_id', 'product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');
    }
};
