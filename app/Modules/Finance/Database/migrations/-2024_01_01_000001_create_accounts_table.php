<?php

// app/Modules/Finance/database/migrations/2024_01_01_000001_create_accounts_table.php
Schema::create('accounts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('code')->unique();
    $table->string('name');
    $table->enum('type', ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE']);
    $table->enum('normal_balance', ['DEBIT', 'CREDIT']);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('fiscal_periods', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['OPEN', 'CLOSED', 'ARCHIVED'])->default('OPEN');
    $table->timestamps();
    $table->unique(['tenant_id', 'name']);
});