<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants', 'id', 'combo_items_tenant_id_fk')->nullOnDelete();
            $table->foreignId('combo_product_id')->constrained('products', 'id', 'combo_items_combo_product_id_fk')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products', 'id', 'combo_items_component_product_id_fk')->cascadeOnDelete();
            $table->foreignId('component_variant_id')->nullable()->constrained('product_variants', 'id', 'combo_items_component_variant_id_fk')->nullOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'combo_items_uom_id_fk');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
