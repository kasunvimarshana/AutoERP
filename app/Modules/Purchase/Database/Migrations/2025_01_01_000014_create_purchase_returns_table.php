<?php
Schema::create('purchase_returns', function (Blueprint $table) {
    $table->id();
    $table->string('return_number')->unique();
    $table->foreignId('purchase_order_id')->constrained();
    $table->foreignId('supplier_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->date('return_date');
    $table->enum('return_type', ['full', 'partial'])->default('partial');
    $table->enum('status', ['draft', 'pending_approval', 'approved', 'returned', 'credited', 'closed'])->default('draft');
    $table->text('reason')->nullable();
    $table->decimal('total_restocking_fee', 15, 4)->default(0);
    $table->string('credit_memo_number')->nullable();
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamps();
});