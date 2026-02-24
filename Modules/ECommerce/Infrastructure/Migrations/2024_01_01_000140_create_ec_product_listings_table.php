<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ec_product_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('inventory_product_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 18, 8);
            $table->decimal('compare_at_price', 18, 8)->nullable();
            $table->string('sku')->nullable();
            $table->boolean('is_published')->default(false);
            $table->integer('stock_quantity')->default(0);
            $table->string('image_url')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_product_listings');
    }
};
