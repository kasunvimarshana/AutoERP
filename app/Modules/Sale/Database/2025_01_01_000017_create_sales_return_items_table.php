<?php

Schema::create('sales_return_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sales_return_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('lot_id')->nullable()->constrained();
    $table->string('serial_number')->nullable();
    $table->decimal('quantity', 15, 4);
    $table->enum('condition', ['good', 'damaged', 'opened'])->default('good');
    $table->enum('restock_action', ['restock', 'quarantine', 'scrap'])->default('restock');
    $table->decimal('restocking_fee', 15, 4)->default(0);
    $table->decimal('refund_amount', 15, 4);
    $table->text('inspection_notes')->nullable();
    $table->timestamps();
});