<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('goods_receipt_id')->index();
            $table->integer('line_number');
            $table->unsignedBigInteger('purchase_order_line_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variation_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_expected', 15, 4)->default(0);
            $table->decimal('quantity_received', 15, 4);
            $table->decimal('quantity_accepted', 15, 4)->default(0);
            $table->decimal('quantity_rejected', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->string('condition', 50)->default('good');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 50)->default('pending');
            $table->unsignedBigInteger('putaway_location_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'goods_receipt_id']);
            $table->index(['goods_receipt_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
