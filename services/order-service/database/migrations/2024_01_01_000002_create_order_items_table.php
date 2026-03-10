<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create order_items table
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('product_id')->index()->comment('Cross-service Product Service reference');
            $table->string('product_name')->default('');
            $table->string('product_code')->default('');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price',      12, 4);
            $table->decimal('discount_amount', 12, 4)->default(0);
            $table->decimal('tax_amount',      12, 4)->default(0);
            $table->decimal('line_total',      12, 4);
            $table->string('currency', 3)->default('USD');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
