<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_reorder_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('location_id')->nullable()->index();
            $table->decimal('reorder_point', 18, 8);
            $table->decimal('min_qty', 18, 8);
            $table->decimal('max_qty', 18, 8);
            $table->integer('lead_time_days')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_reorder_rules'); }
};
