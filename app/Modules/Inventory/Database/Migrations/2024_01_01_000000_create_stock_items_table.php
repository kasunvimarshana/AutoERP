<?php

// app/Modules/Inventory/database/migrations/2024_01_01_000000_create_stock_items_table.php
Schema::create('stock_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('product_variant_id')->constrained();
    $table->foreignUuid('warehouse_location_id')->constrained();
    $table->string('batch_number')->nullable();
    $table->string('lot_number')->nullable();
    $table->string('serial_number')->nullable();
    $table->date('expiry_date')->nullable();
    $table->decimal('quantity_on_hand', 18, 6)->default(0);
    $table->timestamps();
    $table->softDeletes();
    
    // BCNF Compliance: Unique constraint on the combination that defines a distinct stock layer
    $table->unique(['tenant_id', 'product_variant_id', 'warehouse_location_id', 'batch_number', 'lot_number', 'serial_number'], 'stock_items_layer_unique');
});