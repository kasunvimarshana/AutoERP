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
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('grn_number')->unique();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('restrict');
            $table->date('received_date');
            $table->unsignedBigInteger('received_by'); // user_id
            $table->string('status')->default('draft'); // draft, received, partially_received, completed, cancelled
            $table->text('notes')->nullable();
            $table->integer('total_quantity')->default(0);
            $table->text('discrepancy_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'received_date']);
            $table->index('grn_number');
        });

        Schema::create('goods_receipt_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_note_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received');
            $table->integer('quantity_rejected')->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('goods_receipt_note_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_note_items');
        Schema::dropIfExists('goods_receipt_notes');
    }
};
