<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('reservation_number', 80);
            $table->date('reservation_date');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity_reserved', 20, 6);
            $table->decimal('quantity_allocated', 20, 6)->default('0.000000');
            $table->decimal('quantity_released', 20, 6)->default('0.000000');
            $table->decimal('quantity_remaining', 20, 6);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('status', 40)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'reservation_number'], 'inventory_reservations_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_reservations_tenant_org_idx');
            $table->index('item_id', 'inventory_reservations_item_idx');
            $table->index('warehouse_id', 'inventory_reservations_warehouse_idx');
            $table->index(['source_type', 'source_id'], 'inventory_reservations_source_idx');
            $table->index('status', 'inventory_reservations_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
