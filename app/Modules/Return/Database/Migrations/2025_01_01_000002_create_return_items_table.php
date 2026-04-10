<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnItemsTable extends Migration
{
    public function up()
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('return_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->decimal('quantity', 20, 10);
            $table->uuid('uom_id');
            $table->enum('condition', ['new', 'good', 'fair', 'damaged', 'defective', 'expired']);
            $table->enum('disposition', ['restock', 'repair', 'recycle', 'scrap', 'return_to_vendor', 'donate']);
            $table->uuid('original_batch_id')->nullable();
            $table->json('original_serial_numbers')->nullable();
            $table->uuid('new_batch_id')->nullable();
            $table->json('new_serial_numbers')->nullable();
            $table->decimal('restock_quantity', 20, 10)->default(0);
            $table->decimal('scrap_quantity', 20, 10)->default(0);
            $table->decimal('restocking_fee', 20, 6)->default(0);
            $table->decimal('credit_amount', 20, 6);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->text('reason')->nullable();
            $table->json('quality_check_results')->nullable();
            $table->uuid('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
            
            $table->foreign('return_id')->references('id')->on('returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');
            $table->foreign('uom_id')->references('id')->on('uoms');
            $table->foreign('original_batch_id')->references('id')->on('batches');
            $table->foreign('new_batch_id')->references('id')->on('batches');
            $table->index(['return_id', 'product_id']);
            $table->index(['original_batch_id', 'disposition']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_items');
    }
}