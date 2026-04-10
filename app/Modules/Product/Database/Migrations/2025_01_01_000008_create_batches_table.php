<?php

Schema::create('batches', function (Blueprint $table) {
    $table->id();
    $table->string('batch_number', 100)->unique();
    $table->foreignId('product_id')->constrained();
    $table->date('manufacturing_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->decimal('initial_quantity', 15, 4);
    $table->decimal('current_quantity', 15, 4);
    $table->json('supplier_info')->nullable();
    $table->timestamps();
    $table->index(['product_id', 'expiry_date']);
});