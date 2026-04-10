<?php

Schema::create('cycle_count_schedules', function (Blueprint $table) {
    $table->id();
    $table->string('schedule_number')->unique();
    $table->string('name');
    $table->enum('method', ['periodic', 'continuous', 'abc_based', 'random_sampling']);
    $table->enum('status', ['draft', 'active', 'paused', 'completed'])->default('draft');
    $table->foreignId('warehouse_id')->constrained();
    $table->json('filters')->nullable(); // e.g., product categories, location zones
    $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
    $table->integer('days_between_counts')->nullable(); // for periodic
    $table->decimal('sample_percentage', 5, 2)->nullable(); // for random sampling
    $table->json('abc_class_thresholds')->nullable(); // for ABC: e.g., {'A': 0.8, 'B': 0.15, 'C': 0.05}
    $table->timestamp('last_run_at')->nullable();
    $table->timestamp('next_run_at')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});