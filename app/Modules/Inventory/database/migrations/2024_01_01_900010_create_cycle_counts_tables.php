<?php

Schema::create('cycle_count_headers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
    $table->foreignId('warehouse_id')->constrained('warehouses');
    $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
    $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled']);
    $table->foreignId('counted_by_user_id')->nullable()->constrained('users');
    $table->timestamp('counted_at')->nullable();
    $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();
});

Schema::create('cycle_count_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('count_header_id')->constrained('cycle_count_headers')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained('products');
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
    $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
    $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
    $table->decimal('system_qty', 20, 6);
    $table->decimal('counted_qty', 20, 6);
    $table->decimal('variance_qty', 20, 6);
    // $table->decimal('variance_qty', 20, 6)->storedAs('counted_qty - system_qty');
    $table->decimal('unit_cost', 20, 6);
    $table->decimal('variance_value', 20, 6);
    // $table->decimal('variance_value', 20, 6)->storedAs('variance_qty * unit_cost');
    $table->foreignId('adjustment_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
    $table->timestamps();
});
