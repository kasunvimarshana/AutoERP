<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->string('warehouse_code', 50);
            $table->string('warehouse_name', 150);
            $table->string('warehouse_type', 30)->default('standard');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_virtual')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_code']);
            $table->index(['tenant_id', 'org_unit_id']);
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('location_code', 50);
            $table->string('location_name', 150);
            $table->string('location_type', 30)->default('storage');
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->boolean('is_storable')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_id', 'location_code']);
            $table->index(['tenant_id', 'warehouse_id', 'parent_id'], 'warehouse_locations_hierarchy_idx');
        });

        Schema::create('warehouse_location_closure', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->unsignedInteger('depth');
            $table->timestamps();

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth']);
        });

        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('lot_code', 100);
            $table->date('manufactured_at')->nullable();
            $table->date('received_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status_code', 30)->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'lot_code']);
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['tenant_id', 'expiry_date']);
        });

        Schema::create('inventory_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->string('serial_code', 150);
            $table->string('status_code', 30)->default('available');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'serial_code']);
            $table->index(['tenant_id', 'product_variant_id']);
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('qty_on_hand', 24, 8)->default(0);
            $table->decimal('qty_reserved', 24, 8)->default(0);
            $table->decimal('qty_available', 24, 8)->default(0);
            $table->decimal('qty_damaged', 24, 8)->default(0);
            $table->decimal('qty_in_transit', 24, 8)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_id', 'location_id', 'product_variant_id', 'lot_id', 'serial_id', 'unit_of_measure_id'], 'inventory_balances_grain_uk');
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'location_id']);
        });

        Schema::create('inventory_adjustment_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('reason_code', 50);
            $table->string('reason_name', 150);
            $table->string('category_code', 50)->default('general');
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'reason_code']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_number', 100);
            $table->string('movement_type', 50);
            $table->string('source_document_type', 100)->nullable();
            $table->unsignedBigInteger('source_document_id')->nullable();
            $table->string('status_code', 30)->default('posted');
            $table->timestamp('movement_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'movement_number']);
            $table->index(['tenant_id', 'movement_type', 'movement_at']);
            $table->index(['tenant_id', 'warehouse_id', 'movement_at']);
            $table->index(['tenant_id', 'status_code', 'movement_at']);
        });

        Schema::create('stock_movement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('source_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('quantity', 24, 8);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('total_cost', 20, 6)->nullable();
            $table->string('line_action', 30)->default('move');
            $table->timestamps();

            $table->index(['tenant_id', 'stock_movement_id', 'product_variant_id'], 'stock_movement_lines_header_product_idx');
            $table->index(['tenant_id', 'product_variant_id', 'created_at']);
            $table->index(['tenant_id', 'source_location_id']);
            $table->index(['tenant_id', 'destination_location_id']);
        });

        Schema::create('inventory_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('uoms')->cascadeOnDelete();
            $table->string('layer_type', 30)->default('receipt');
            $table->decimal('qty_received', 24, 8);
            $table->decimal('qty_remaining', 24, 8);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index(['tenant_id', 'product_variant_id', 'warehouse_id', 'received_at'], 'inventory_layers_lookup_idx');
            $table->index(['tenant_id', 'lot_id']);
            $table->index(['tenant_id', 'serial_id']);
            $table->index(['tenant_id', 'qty_remaining']);
        });

        Schema::create('inventory_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('inventory_layer_id')->constrained('inventory_layers')->cascadeOnDelete();
            $table->foreignId('stock_movement_line_id')->constrained('stock_movement_lines')->cascadeOnDelete();
            $table->decimal('qty_consumed', 24, 8);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->timestamps();

            $table->index(['tenant_id', 'inventory_layer_id']);
        });

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->decimal('quantity_reserved', 24, 8);
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status_code', 30)->default('active');
            $table->timestamps();

            $table->index(['tenant_id', 'source_type', 'source_id']);
            $table->index(['tenant_id', 'product_variant_id', 'warehouse_id']);
            $table->index(['tenant_id', 'status_code', 'expires_at']);
        });

        Schema::create('stock_count_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('count_number', 100);
            $table->string('count_type', 50)->default('cycle');
            $table->string('status_code', 30)->default('draft');
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'count_number']);
            $table->index(['tenant_id', 'warehouse_id', 'status_code']);
            $table->index(['tenant_id', 'counted_at']);
        });

        Schema::create('stock_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('stock_count_session_id')->constrained('stock_count_sessions')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('inventory_lots')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('inventory_serials')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('system_quantity', 24, 8)->default(0);
            $table->decimal('counted_quantity', 24, 8)->default(0);
            $table->decimal('variance_quantity', 24, 8)->default(0);
            $table->string('variance_reason', 150)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['tenant_id', 'stock_count_session_id', 'product_variant_id'], 'stock_count_lines_session_product_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_lines');
        Schema::dropIfExists('stock_count_sessions');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('inventory_layer_consumptions');
        Schema::dropIfExists('inventory_layers');
        Schema::dropIfExists('stock_movement_lines');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_adjustment_reasons');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_serials');
        Schema::dropIfExists('inventory_lots');
        Schema::dropIfExists('warehouse_location_closure');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
    }
};
