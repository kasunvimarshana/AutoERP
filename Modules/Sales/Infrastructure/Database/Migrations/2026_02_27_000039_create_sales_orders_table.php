<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->string('order_number');
            $table->string('status')->default('quotation')->comment('quotation/confirmed/in_delivery/invoiced/paid/cancelled');
            $table->date('order_date');
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('subtotal', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default('0.0000');
            $table->decimal('tax_amount', 20, 4)->default('0.0000');
            $table->decimal('total_amount', 20, 4);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'order_number']);
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
