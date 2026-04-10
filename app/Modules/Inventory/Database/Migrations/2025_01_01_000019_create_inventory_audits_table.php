<?php

Schema::create('inventory_audits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('action'); // create, update, delete, adjust, move, return
    $table->string('auditable_type');
    $table->unsignedBigInteger('auditable_id');
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['auditable_type', 'auditable_id']);
});