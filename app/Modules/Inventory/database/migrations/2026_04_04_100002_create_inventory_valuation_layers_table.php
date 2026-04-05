<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_valuation_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->decimal('quantity', 15, 4);
            $table->decimal('quantity_remaining', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->timestamp('received_at');
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id','product_id','warehouse_id','received_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_valuation_layers'); }
};
