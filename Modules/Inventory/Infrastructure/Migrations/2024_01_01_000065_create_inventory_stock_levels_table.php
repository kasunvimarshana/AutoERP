<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_stock_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('variant_id')->nullable()->index();
            $table->uuid('location_id')->index();
            $table->decimal('qty', 18, 8)->default(0);
            $table->decimal('reserved_qty', 18, 8)->default(0);
            $table->timestamps();
            $table->unique(['tenant_id', 'product_id', 'location_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_stock_levels'); }
};
