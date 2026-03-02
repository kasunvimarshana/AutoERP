<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('vendor_id');
            $table->string('order_number');
            $table->string('status')->default('draft')->comment('draft/sent/confirmed/goods_received/invoiced/paid/cancelled');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'order_number']);
            $table->foreign('vendor_id')->references('id')->on('vendors')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
