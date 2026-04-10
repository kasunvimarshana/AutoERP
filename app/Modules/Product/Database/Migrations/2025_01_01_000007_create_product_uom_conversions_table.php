<?php

Schema::create('product_uom_conversions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('from_uom_id')->constrained('unit_of_measures');
    $table->foreignId('to_uom_id')->constrained('unit_of_measures');
    $table->decimal('factor', 15, 6); // 1 from_uom = factor to_uom
    $table->timestamps();
    $table->unique(['product_id', 'from_uom_id', 'to_uom_id'], 'uom_conversion_unique');
});