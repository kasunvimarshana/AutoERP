<?php

Schema::create('inventory_layers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->date('received_date');
    $table->date('expiry_date')->nullable();
    $table->decimal('quantity', 15, 4);
    $table->decimal('remaining_quantity', 15, 4);
    $table->decimal('unit_cost', 15, 4);
    $table->string('reference_type'); // purchase, return, adjustment
    $table->unsignedBigInteger('reference_id');
    $table->timestamps();
    $table->index(['product_id', 'warehouse_id', 'received_date']);
});