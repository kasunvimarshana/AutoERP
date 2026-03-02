<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('pos_order_id')->constrained('pos_orders');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'pos_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_lines');
    }
};
