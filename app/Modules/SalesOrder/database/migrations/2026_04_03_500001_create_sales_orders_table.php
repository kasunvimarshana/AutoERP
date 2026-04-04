<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('so_number', 100);
            $table->string('status', 20)->default('draft');
            $table->decimal('total_amount', 15, 4)->nullable();
            $table->decimal('tax_amount', 15, 4)->nullable();
            $table->decimal('discount_amount', 15, 4)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->unsignedBigInteger('picked_by')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->unsignedBigInteger('packed_by')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'so_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
