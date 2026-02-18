<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('attribute_name');
            $table->text('attribute_value');
            $table->string('attribute_type', 50)->default('text'); // text, number, boolean, date
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'attribute_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
