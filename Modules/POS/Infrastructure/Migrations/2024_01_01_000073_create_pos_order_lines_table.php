<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pos_order_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->string('product_name');
            $table->decimal('quantity', 18, 8)->default(1);
            $table->decimal('unit_price', 18, 8)->default(0);
            $table->decimal('discount', 18, 8)->default(0);
            $table->decimal('tax_rate', 18, 8)->default(0);
            $table->decimal('line_total', 18, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_order_lines');
    }
};
