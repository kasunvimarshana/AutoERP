<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('min_quantity', 15, 4)->default(1);
            $table->decimal('max_quantity', 15, 4)->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('markup_percent', 5, 2)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->string('uom_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
