<?php

// app/Modules/Core/database/migrations/2024_01_01_000001_create_organization_units_table.php
Schema::create('organization_units', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('parent_id')->nullable()->constrained('organization_units')->nullOnDelete();
    $table->string('name');
    $table->enum('type', ['COMPANY', 'DIVISION', 'DEPARTMENT', 'TEAM']);
    $table->string('path')->nullable(); // 或使用 ltree 扩展
    $table->timestamps();
    $table->softDeletes();
    $table->index(['tenant_id', 'parent_id']);
});