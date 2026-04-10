<?php

Schema::create('cycle_count_results', function (Blueprint $table) {
    $table->id();
    $table->foreignId('schedule_id')->constrained('cycle_count_schedules');
    $table->timestamp('run_at');
    $table->integer('total_items');
    $table->integer('items_counted');
    $table->integer('items_with_discrepancy');
    $table->decimal('total_variance', 15, 4)->nullable();
    $table->json('summary')->nullable(); // statistics
    $table->timestamps();
});