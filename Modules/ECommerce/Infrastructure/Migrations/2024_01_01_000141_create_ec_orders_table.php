<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ec_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('reference_no');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('tax_amount', 18, 8)->default(0);
            $table->decimal('shipping_cost', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_orders');
    }
};
