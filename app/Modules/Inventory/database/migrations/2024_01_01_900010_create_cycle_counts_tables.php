<?php

Schema::create('cycle_count_headers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants', 'id', 'cycle_count_headers_tenant_id_fk')->cascadeOnDelete();
    $table->foreignId('warehouse_id')->constrained('warehouses', 'id', 'cycle_count_headers_warehouse_id_fk');
    $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id', 'cycle_count_headers_location_id_fk')->nullOnDelete();
    $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled']);
    $table->foreignId('counted_by_user_id')->nullable()->constrained('users', 'id', 'cycle_count_headers_counted_by_user_id_fk');
    $table->timestamp('counted_at')->nullable();
    $table->foreignId('approved_by_user_id')->nullable()->constrained('users', 'id', 'cycle_count_headers_approved_by_user_id_fk');
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
});

Schema::create('cycle_count_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('count_header_id')->constrained('cycle_count_headers', 'id', 'cycle_count_lines_count_header_id_fk')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products', 'id', 'cycle_count_lines_product_id_fk');
    $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'cycle_count_lines_variant_id_fk')->nullOnDelete();
    $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'cycle_count_lines_batch_id_fk')->nullOnDelete();
    $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'cycle_count_lines_serial_id_fk')->nullOnDelete();
    $table->decimal('system_qty', 20, 6);
    $table->decimal('counted_qty', 20, 6);
    $table->decimal('variance_qty', 20, 6);
    // $table->decimal('variance_qty', 20, 6)->storedAs('counted_qty - system_qty');
    $table->decimal('unit_cost', 20, 6);
    $table->decimal('variance_value', 20, 6);
    // $table->decimal('variance_value', 20, 6)->storedAs('variance_qty * unit_cost');
    $table->foreignId('adjustment_movement_id')->nullable()->constrained('stock_movements', 'id', 'cycle_count_lines_adjustment_movement_id_fk')->nullOnDelete();
    $table->timestamps();
});
