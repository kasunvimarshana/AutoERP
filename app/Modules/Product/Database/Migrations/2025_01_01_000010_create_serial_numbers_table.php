<?php

Schema::create('serial_numbers', function (Blueprint $table) {
    $table->id();
    $table->string('serial', 100)->unique();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('lot_id')->nullable()->constrained();
    $table->enum('status', ['available', 'reserved', 'sold', 'returned', 'damaged'])->default('available');
    $table->foreignId('current_location_id')->nullable()->constrained('locations');
    $table->timestamps();
    $table->index(['product_id', 'status']);
});