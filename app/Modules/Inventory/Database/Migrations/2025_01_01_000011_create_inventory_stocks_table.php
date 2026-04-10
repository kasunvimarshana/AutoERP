<?php

Schema::create('inventory_stocks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants');
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('location_id')->nullable()->constrained('locations');
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('lot_id')->nullable()->constrained();
    $table->foreignId('serial_id')->nullable()->constrained('serial_numbers');
    $table->decimal('quantity', 15, 4)->default(0);
    $table->decimal('reserved_quantity', 15, 4)->default(0);
    $table->decimal('unit_cost', 15, 4)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->unique(
        ['product_id', 'variant_id', 'warehouse_id', 'location_id', 'batch_id', 'lot_id', 'serial_id'],
        'stock_unique_key'
    );
});