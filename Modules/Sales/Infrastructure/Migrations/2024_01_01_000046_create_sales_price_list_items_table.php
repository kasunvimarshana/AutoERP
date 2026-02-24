<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_price_list_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('price_list_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('variant_id')->nullable()->index();
            $table->string('strategy')->default('flat');
            $table->decimal('amount', 18, 8)->default(0);
            $table->decimal('min_qty', 18, 8)->default(1);
            $table->string('uom')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('sales_price_list_items'); }
};
