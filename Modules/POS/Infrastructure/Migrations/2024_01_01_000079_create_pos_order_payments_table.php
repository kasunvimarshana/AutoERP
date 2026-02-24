<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_order_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('order_id')->index();
            $table->string('payment_method', 50);
            $table->decimal('amount', 18, 8)->default(0);
            $table->string('reference', 255)->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                  ->references('id')
                  ->on('pos_orders')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_payments');
    }
};
