<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'variant_attributes_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'id', 'variant_attributes_product_id_fk')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes', 'id', 'variant_attributes_attribute_id_fk')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_variation_axis')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'product_id', 'attribute_id'], 'variant_attributes_tenant_product_attr_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attributes');
    }
};
