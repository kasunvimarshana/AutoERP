<?php

Schema::create('lots', function (Blueprint $table) {
    $table->id();
    $table->string('lot_number', 100)->unique();
    $table->foreignId('product_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->date('production_date')->nullable();
    $table->date('expiry_date')->nullable();
    $table->decimal('quantity', 15, 4);
    $table->timestamps();
});