<?php

Schema::create('cycle_count_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('schedule_id')->constrained('cycle_count_schedules');
    $table->foreignId('product_id')->constrained();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants');
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('location_id')->nullable()->constrained();
    $table->foreignId('batch_id')->nullable()->constrained();
    $table->foreignId('serial_id')->nullable()->constrained('serial_numbers');
    $table->decimal('expected_quantity', 15, 4);
    $table->decimal('counted_quantity', 15, 4)->nullable();
    $table->enum('status', ['pending', 'in_progress', 'counted', 'verified', 'adjusted'])->default('pending');
    $table->foreignId('counted_by')->nullable()->constrained('users');
    $table->timestamp('counted_at')->nullable();
    $table->foreignId('verified_by')->nullable()->constrained('users');
    $table->timestamp('verified_at')->nullable();
    $table->text('notes')->nullable();
    $table->json('adjustment_details')->nullable(); // if adjustment was made
    $table->timestamps();
});