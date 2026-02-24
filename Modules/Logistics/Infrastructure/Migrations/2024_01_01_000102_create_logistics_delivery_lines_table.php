<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('logistics_delivery_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('delivery_order_id')->index();
            $table->uuid('product_id')->index();
            $table->string('product_name');
            $table->decimal('quantity', 18, 8);
            $table->string('unit')->default('pcs');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_delivery_lines');
    }
};
