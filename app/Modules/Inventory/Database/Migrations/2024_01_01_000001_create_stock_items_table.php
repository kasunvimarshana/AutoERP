<?php

// app/Modules/Inventory/database/migrations/2024_01_01_000001_create_stock_items_table.php
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
    
    // BCNF：确保一个租户内同一库存层唯一
    $table->unique(['tenant_id', 'product_variant_id', 'warehouse_location_id', 'batch_number', 'lot_number', 'serial_number'], 'stock_items_layer_unique');
    $table->index(['product_variant_id', 'warehouse_location_id']);
});

// 库存移动（不可变）
Schema::create('stock_movements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('stock_item_id')->constrained()->cascadeOnDelete();
    $table->enum('movement_type', ['RECEIPT', 'ISSUE', 'TRANSFER', 'RETURN', 'ADJUSTMENT']);
    $table->decimal('quantity', 18, 6);
    $table->decimal('unit_cost', 18, 6);
    $table->uuidMorphs('reference'); // 多态关联 goods_receipts/shipments/return_orders
    $table->timestamp('transaction_date')->useCurrent();
    $table->timestamps();
    $table->index(['tenant_id', 'stock_item_id', 'created_at']);
});