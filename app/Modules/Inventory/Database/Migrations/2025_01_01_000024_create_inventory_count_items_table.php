<?php

Schema::create('inventory_count_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('inventory_count_id')->constrained('inventory_counts');
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants');
    $table->foreignId('location_id')->nullable()->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('serial_id')->nullable()->constrained('serial_numbers');
    $table->decimal('system_quantity', 15, 4);
    $table->decimal('counted_quantity', 15, 4);
    $table->decimal('variance', 15, 4)->storedAs('counted_quantity - system_quantity');
    $table->text('notes')->nullable();
    $table->timestamps();
});