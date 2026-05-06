<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->foreignId('job_card_id')->constrained('service_job_cards')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('total_amount', 20, 6);
            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('inventory_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->boolean('is_warranty_replacement')->default(false);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_parts');
    }
};
