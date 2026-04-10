<?php

Schema::create('inventory_transactions', function (Blueprint $table) {
    $table->id();
    $table->uuid('transaction_id')->unique();
    $table->string('type'); // purchase, sales, return_in, return_out, adjustment, transfer
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants');
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('location_id')->nullable()->constrained('locations');
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('lot_id')->nullable()->constrained();
    $table->string('serial_number')->nullable();
    $table->decimal('quantity', 15, 4);
    $table->decimal('unit_cost', 15, 4);
    $table->decimal('total_cost', 15, 4);
    $table->enum('direction', ['in', 'out']);
    $table->string('reference_type'); // purchase_order, sales_order, return, adjustment
    $table->unsignedBigInteger('reference_id');
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->index(['product_id', 'batch_id']);
    $table->index(['reference_type', 'reference_id']);
});