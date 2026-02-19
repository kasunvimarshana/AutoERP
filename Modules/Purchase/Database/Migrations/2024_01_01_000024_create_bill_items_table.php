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
        Schema::create('bill_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('bill_id');
            $table->ulid('product_id');
            $table->ulid('unit_id');
            $table->ulid('purchase_order_item_id')->nullable();
            $table->ulid('goods_receipt_item_id')->nullable();
            $table->string('description');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6);
            $table->decimal('total', 20, 6);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('bill_id')
                ->references('id')
                ->on('bills')
                ->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'bill_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'purchase_order_item_id']);
            $table->index(['tenant_id', 'goods_receipt_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
