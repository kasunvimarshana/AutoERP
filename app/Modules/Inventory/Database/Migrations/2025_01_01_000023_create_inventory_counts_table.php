<?php

Schema::create('inventory_counts', function (Blueprint $table) {
    $table->id();
    $table->string('count_number')->unique();
    $table->enum('type', ['manual', 'cycle', 'year_end']);
    $table->foreignId('warehouse_id')->constrained();
    $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->json('metadata')->nullable();
    $table->timestamps();
});