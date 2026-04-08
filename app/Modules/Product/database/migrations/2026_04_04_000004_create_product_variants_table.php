<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('product_id');
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->nullable();
            $table->string('name', 300);
            $table->json('attributes')->nullable(); // e.g. {"color":"red","size":"M"}
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id']);

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
