<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
$table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
$table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('price_list_id')->constrained(null, 'id', 'price_list_items_price_list_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(null, 'id', 'price_list_items_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'price_list_items_variant_id_fk')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'price_list_items_uom_id_fk');
            $table->decimal('min_quantity', 20, 6)->default(1);
            $table->decimal('price', 20, 6);
            $table->decimal('discount_pct', 10, 6)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'price_list_id', 'product_id', 'variant_id', 'uom_id', 'min_quantity'], 'price_list_items_tenant_pricelist_product_var_uom_minqty_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
