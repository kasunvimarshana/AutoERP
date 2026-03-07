<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('sku', 100)->nullable();
            $table->string('name')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('total_price', 12, 4);
            $table->decimal('tax_rate', 6, 4)->default(0);
            $table->decimal('discount', 12, 4)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
