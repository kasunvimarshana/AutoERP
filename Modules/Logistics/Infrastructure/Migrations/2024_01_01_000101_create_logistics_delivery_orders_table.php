<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logistics_delivery_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('carrier_id')->nullable()->index();
            $table->string('reference_no');
            $table->text('origin_address');
            $table->text('destination_address');
            $table->date('scheduled_date');
            $table->date('delivered_date')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('weight', 10, 3)->default(0);
            $table->decimal('shipping_cost', 18, 8)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_delivery_orders');
    }
};
