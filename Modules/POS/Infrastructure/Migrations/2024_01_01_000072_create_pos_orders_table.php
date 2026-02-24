<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('session_id')->index();
            $table->string('number');
            $table->uuid('customer_id')->nullable()->index();
            $table->string('status')->default('pending');
            $table->string('payment_method')->default('cash');
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('tax_total', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->decimal('cash_tendered', 18, 8)->nullable();
            $table->decimal('change_amount', 18, 8)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
