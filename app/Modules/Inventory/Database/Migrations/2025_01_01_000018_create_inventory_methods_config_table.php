<?php

Schema::create('inventory_methods_config', function (Blueprint $table) {
    $table->id();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('product_id')->nullable()->constrained(); // null = global
    $table->enum('valuation_method', ['fifo', 'lifo', 'weighted_average', 'specific_identification']);
    $table->enum('rotation_strategy', ['fefo', 'fifo', 'lifo', 'based_on_location']);
    $table->enum('allocation_algorithm', ['nearest_expiry', 'oldest_stock', 'nearest_location', 'manual']);
    $table->boolean('allow_negative_stock')->default(false);
    $table->timestamps();
});