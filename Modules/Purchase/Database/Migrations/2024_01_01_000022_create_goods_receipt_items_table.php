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
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('goods_receipt_id');
            $table->ulid('purchase_order_item_id');
            $table->ulid('product_id');
            $table->ulid('unit_id');
            $table->string('description');
            $table->decimal('quantity_ordered', 20, 6);
            $table->decimal('quantity_received', 20, 6);
            $table->decimal('quantity_accepted', 20, 6);
            $table->decimal('quantity_rejected', 20, 6)->default(0);
            $table->string('rejection_reason')->nullable();
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('goods_receipt_id')
                ->references('id')
                ->on('goods_receipts')
                ->onDelete('cascade');

            // Indexes
            $table->index(['tenant_id', 'goods_receipt_id']);
            $table->index(['tenant_id', 'purchase_order_item_id']);
            $table->index(['tenant_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
