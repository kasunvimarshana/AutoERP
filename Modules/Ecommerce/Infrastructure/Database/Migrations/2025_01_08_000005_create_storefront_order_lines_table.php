<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('storefront_orders')->onDelete('cascade');
            $table->index(['order_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_order_lines');
    }
};
