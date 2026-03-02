<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('discount_rate', 8, 4)->default(0);
            $table->decimal('line_total', 15, 4);
            $table->timestamps();

            $table->foreign('sales_order_id')
                ->references('id')
                ->on('sales_orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
