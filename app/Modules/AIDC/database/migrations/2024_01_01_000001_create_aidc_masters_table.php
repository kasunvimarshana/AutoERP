<?php

// app/Modules/AIDC/database/migrations/2024_01_01_000001_create_aidc_masters_table.php
Schema::create('aidc_masters', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
    $table->uuidMorphs('target'); // 关联 product_variants / stock_items / warehouse_locations
    $table->timestamps();
    $table->unique(['tenant_id', 'target_type', 'target_id']);
});

Schema::create('aidc_identifiers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('aidc_master_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['GTIN', 'EAN13', 'CODE128', 'QR', 'EPC_URN', 'RFID_TID', 'CUSTOM']);
    $table->string('value');
    $table->boolean('is_primary')->default(false);
    $table->timestamps();
    $table->unique(['type', 'value'], 'aidc_identifiers_type_value_unique');
    $table->index(['aidc_master_id', 'is_primary']);
});