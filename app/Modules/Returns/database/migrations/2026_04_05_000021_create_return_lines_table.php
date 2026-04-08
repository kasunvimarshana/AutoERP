<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_lines', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('return_id');
            $table->foreign('return_id')->references('id')->on('returns')->cascadeOnDelete();
            $table->unsignedBigInteger('order_line_id')->nullable();
            $table->foreign('order_line_id')->references('id')->on('order_lines')->nullOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->unsignedBigInteger('batch_lot_id')->nullable();
            $table->foreign('batch_lot_id')->references('id')->on('batch_lots')->nullOnDelete();
            $table->unsignedBigInteger('serial_number_id')->nullable();
            $table->foreign('serial_number_id')->references('id')->on('serial_numbers')->nullOnDelete();
            $table->decimal('quantity_requested', 20, 6);
            $table->decimal('quantity_approved', 20, 6)->default(0);
            $table->decimal('quantity_received', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('subtotal', 20, 6);
            $table->enum('quality_check_result', ['passed', 'failed', 'pending', 'quarantine'])->default('pending');
            $table->text('quality_notes')->nullable();
            $table->text('condition_notes')->nullable();
            $table->enum('restock_action', ['restock', 'scrap', 'quarantine', 'return_to_supplier'])->default('restock');
            $table->timestamps();

            $table->index(['return_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_lines');
    }
};
