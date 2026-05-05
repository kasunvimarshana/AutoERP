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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('product_id')->constrained(null, 'id', 'stock_reservations_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_reservations_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained(null, 'id', 'stock_reservations_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained(null, 'id', 'stock_reservations_serial_id_fk')->nullOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations', 'id', 'stock_reservations_location_id_fk')->cascadeOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->nullableMorphs('reserved_for'); // e.g., sales order line
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
