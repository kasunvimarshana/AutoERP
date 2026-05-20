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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('price_list_id')->constrained('price_lists', 'id')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('item_variants', 'id')->nullable()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses', 'id')->nullable()->cascadeOnDelete();
            $table->foreignId('warehouse_location_id')->constrained('warehouse_locations', 'id')->nullable()->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('batches', 'id')->nullable()->cascadeOnDelete();
            $table->foreignId('serial_id')->constrained('serials', 'id')->nullable()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id', 'price_list_items_uom_id_fk');
            $table->decimal('min_quantity', 20, 4)->default(1);
            $table->decimal('price', 20, 4);
            // $table->decimal('discount_pct', 20, 4)->default(0);
            $table->string('discount_type')->default('percentage')->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'price_list_id', 'item_id', 'variant_id', 'warehouse_id', 'warehouse_location_id', 'batch_id', 'serial_id', 'uom_id', 'min_quantity'], 'price_list_items_unique_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
    }
};
