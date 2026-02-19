<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('order_id');
            $table->ulid('product_id');
            $table->text('description')->nullable();
            $table->decimal('quantity', 20, 6)->default(1);
            $table->ulid('unit_id');
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('total', 20, 6)->default(0);
            $table->decimal('quantity_shipped', 20, 6)->default(0);
            $table->decimal('quantity_invoiced', 20, 6)->default(0);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'order_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'order_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
