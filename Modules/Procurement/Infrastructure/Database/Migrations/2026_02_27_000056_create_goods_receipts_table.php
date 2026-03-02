<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('receipt_number');
            $table->string('status')->default('draft')->comment('draft/received/cancelled');
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'receipt_number']);
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
