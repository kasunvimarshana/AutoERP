<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_variation_location_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('variation_id');
            $table->uuid('location_id');
            $table->decimal('qty_available', 15, 2)->default(0);
            $table->uuid('selling_price_group_id')->nullable();
            $table->decimal('group_price', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('pos_product_variations')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('pos_business_locations')->onDelete('cascade');
            $table->foreign('selling_price_group_id')->references('id')->on('pos_selling_price_groups')->onDelete('set null');
            $table->index(['tenant_id', 'variation_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_variation_location_details');
    }
};
