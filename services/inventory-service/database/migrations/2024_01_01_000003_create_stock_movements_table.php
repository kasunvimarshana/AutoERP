<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 36);
            $table->uuid('product_id');
            $table->string('warehouse_id', 36);
            $table->enum('movement_type', ['IN', 'OUT', 'RESERVE', 'RELEASE', 'ADJUSTMENT', 'RETURN', 'TRANSFER']);
            $table->integer('quantity');
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_after')->nullable();
            $table->string('reference_id', 36)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('performed_by', 36)->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('product_id');
            $table->index('movement_type');
            $table->index(['reference_id', 'reference_type']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
