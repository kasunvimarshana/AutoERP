<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_quotation_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quotation_id')->index();
            $table->uuid('product_id')->nullable()->index();
            $table->string('description');
            $table->decimal('qty', 18, 8)->default(1);
            $table->decimal('unit_price', 18, 8)->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 8)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 18, 8)->default(0);
            $table->decimal('line_total', 18, 8)->default(0);
            $table->string('uom')->default('unit');
            $table->integer('sort_order')->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('sales_quotation_lines'); }
};
