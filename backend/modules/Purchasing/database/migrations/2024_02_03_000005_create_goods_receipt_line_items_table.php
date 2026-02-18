<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->onDelete('cascade');
            $table->foreignId('purchase_order_line_item_id')->constrained('purchase_order_line_items')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('restrict');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('restrict');
            $table->decimal('ordered_quantity', 15, 4);
            $table->decimal('received_quantity', 15, 4);
            $table->decimal('accepted_quantity', 15, 4)->default(0);
            $table->decimal('rejected_quantity', 15, 4)->default(0);
            $table->string('unit_of_measure', 20);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 2);
            $table->string('batch_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('inspection_status', 50)->nullable(); // pending, passed, failed
            $table->text('inspection_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'goods_receipt_id']);
            $table->index('purchase_order_line_item_id');
            $table->index('product_id');
            $table->index('batch_number');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_line_items');
    }
};
