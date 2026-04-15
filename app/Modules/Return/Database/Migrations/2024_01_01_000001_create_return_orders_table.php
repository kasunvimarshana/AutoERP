<?php

// app/Modules/Return/database/migrations/2024_01_01_000001_create_return_orders_table.php
Schema::create('return_orders', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('party_id')->constrained('parties');
    $table->enum('return_type', ['PURCHASE', 'SALES']);
    $table->uuidMorphs('source'); // 多态指向 purchase_orders / sales_orders / 直接为 null
    $table->string('return_number')->unique();
    $table->date('return_date');
    $table->enum('status', ['DRAFT', 'AUTHORIZED', 'RECEIVED', 'INSPECTED', 'COMPLETED', 'CANCELLED']);
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('return_order_lines', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('return_order_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('product_variant_id')->constrained();
    $table->foreignUuid('stock_item_id')->nullable()->constrained('stock_items'); // 若指定批次/序列则必填
    $table->decimal('quantity', 18, 6);
    $table->enum('condition', ['GOOD', 'DAMAGED'])->default('GOOD');
    $table->decimal('restocking_fee', 18, 6)->default(0);
    $table->decimal('unit_price', 18, 6); // 退款单价
    $table->timestamps();
});