<?php

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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('receipt_number', 50)->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->date('received_date');
            $table->unsignedBigInteger('received_by');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->enum('matched_status', ['unmatched', 'partial', 'matched'])->default('unmatched');
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('receipt_number');
            $table->index('purchase_order_id');
            $table->index('status');
            $table->index('matched_status');
            $table->index('warehouse_id');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'purchase_order_id']);

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
