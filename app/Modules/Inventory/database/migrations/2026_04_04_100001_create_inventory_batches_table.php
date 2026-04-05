<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('batch_number');
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('quantity_remaining', 15, 4);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->timestamp('manufactured_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('received_at');
            $table->string('status')->default('active');
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id','product_id','warehouse_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_batches'); }
};
