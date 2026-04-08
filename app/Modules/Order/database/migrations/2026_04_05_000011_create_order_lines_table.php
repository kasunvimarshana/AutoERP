<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_lines', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->unsignedSmallInteger('line_number')->default(1);
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 20, 6);
            $table->string('unit_of_measure', 50)->nullable();
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6);
            $table->decimal('total', 20, 6);
            $table->decimal('quantity_received', 20, 6)->default(0);
            $table->decimal('quantity_delivered', 20, 6)->default(0);
            $table->unsignedBigInteger('batch_lot_id')->nullable();
            $table->foreign('batch_lot_id')->references('id')->on('batch_lots')->nullOnDelete();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'line_number']);
            $table->index(['order_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
