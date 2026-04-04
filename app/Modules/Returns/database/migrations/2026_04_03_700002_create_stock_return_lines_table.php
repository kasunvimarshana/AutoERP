<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_return_id')->constrained('stock_returns')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('return_qty', 15, 4);
            $table->string('condition', 20)->default('good');
            $table->string('quality_check_result', 20)->default('pending');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('original_batch_id')->nullable();
            $table->string('original_lot_number', 100)->nullable();
            $table->string('original_serial_number', 100)->nullable();
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->decimal('line_total', 15, 4)->nullable();
            $table->string('restock_action', 30)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('quality_checked_by')->nullable();
            $table->timestamp('quality_checked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_return_lines');
    }
};
