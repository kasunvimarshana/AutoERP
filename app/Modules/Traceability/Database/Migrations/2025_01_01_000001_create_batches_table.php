<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('batch_number')->unique();
            $table->string('lot_number')->nullable();
            $table->uuid('product_id');
            $table->uuid('supplier_id')->nullable();
            $table->uuid('manufacturer_id')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('best_before_date')->nullable();
            $table->json('certificates')->nullable();
            $table->json('quality_metrics')->nullable();
            $table->enum('status', ['active', 'expired', 'quarantined', 'recalled', 'depleted'])->default('active');
            $table->text('notes')->nullable();
            $table->decimal('initial_quantity', 15, 4);
            $table->decimal('current_quantity', 15, 4);
            $table->json('supplier_info')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products');
            $table->index(['batch_number', 'product_id', 'expiry_date', 'status']);
            $table->index(['product_id', 'expiry_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('batches');
    }
}