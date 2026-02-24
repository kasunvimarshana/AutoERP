<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ec_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('order_id')->index();
            $table->uuid('product_listing_id')->nullable()->index();
            $table->string('product_name');
            $table->decimal('unit_price', 18, 8);
            $table->decimal('quantity', 18, 8);
            $table->decimal('discount', 18, 8)->default(0);
            $table->decimal('tax_rate', 18, 8)->default(0);
            $table->decimal('line_total', 18, 8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ec_order_lines');
    }
};
