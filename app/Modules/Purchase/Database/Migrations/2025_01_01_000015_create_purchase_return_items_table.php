<?php

Schema::create('purchase_return_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_return_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('lot_id')->nullable()->constrained();
    $table->string('serial_number')->nullable();
    $table->decimal('quantity', 15, 4);
    $table->enum('condition', ['good', 'damaged', 'expired'])->default('good');
    $table->decimal('restocking_fee', 15, 4)->default(0);
    $table->decimal('unit_cost', 15, 4);
    $table->enum('disposition', ['return_to_vendor', 'scrap', 'recycle'])->default('return_to_vendor');
    $table->text('quality_check_notes')->nullable();
    $table->timestamps();
});