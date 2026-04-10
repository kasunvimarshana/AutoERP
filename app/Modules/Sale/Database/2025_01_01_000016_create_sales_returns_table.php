<?php

Schema::create('sales_returns', function (Blueprint $table) {
    $table->id();
    $table->string('return_number')->unique();
    $table->foreignId('sales_order_id')->constrained();
    $table->foreignId('customer_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->date('return_date');
    $table->enum('return_type', ['full', 'partial'])->default('partial');
    $table->enum('status', ['requested', 'received', 'inspected', 'restocked', 'credited', 'closed'])->default('requested');
    $table->text('reason')->nullable();
    $table->decimal('restocking_fee', 15, 4)->default(0);
    $table->string('credit_memo_number')->nullable();
    $table->timestamps();
});