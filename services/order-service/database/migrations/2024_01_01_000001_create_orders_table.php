<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('customer_id')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('items');
            $table->string('saga_id')->unique()->nullable();
            $table->string('saga_status')->default('started');
            $table->string('payment_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
