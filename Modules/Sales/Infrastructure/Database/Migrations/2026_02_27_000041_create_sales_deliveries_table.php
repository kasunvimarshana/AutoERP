<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('sales_order_id');
            $table->string('delivery_number');
            $table->string('status')->default('pending')->comment('pending/in_transit/delivered/cancelled');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_deliveries');
    }
};
