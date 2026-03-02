<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('purchase_order_line_id');
            $table->decimal('quantity_received', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->cascadeOnDelete();
            $table->foreign('purchase_order_line_id')->references('id')->on('purchase_order_lines')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
