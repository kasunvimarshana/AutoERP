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

// 会计期间
Schema::create('fiscal_periods', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['OPEN', 'CLOSED', 'ARCHIVED'])->default('OPEN');
    $table->timestamps();
    $table->unique(['tenant_id', 'name']);
    $table->index(['tenant_id', 'status']);
});

// 日记账分录行（带CHECK约束需用原始SQL）
Schema::create('journal_entry_lines', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('journal_entry_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('account_id')->constrained();
    $table->decimal('debit', 18, 6)->default(0);
    $table->decimal('credit', 18, 6)->default(0);
    $table->text('description')->nullable();
    $table->timestamps();
    // 业务约束：至少一边非零，且互斥（由应用层或触发器保证）
});