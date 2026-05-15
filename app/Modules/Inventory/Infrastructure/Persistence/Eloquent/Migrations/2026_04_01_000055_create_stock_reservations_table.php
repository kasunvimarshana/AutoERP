<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations');
            $table->decimal('quantity', 20, 4);
            $table->nullableMorphs('reserved_for');
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'expires_at'], 'stock_reservations_expiry_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'product_id', 'location_id'], 'stock_reservations_product_location_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'reserved_for_type', 'reserved_for_id'], 'stock_reservations_reserved_for_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
