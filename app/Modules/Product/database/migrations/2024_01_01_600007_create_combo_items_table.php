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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('combo_product_id')->constrained('products', 'id', 'combo_items_combo_product_id_fk')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products', 'id', 'combo_items_component_product_id_fk')->cascadeOnDelete();
            $table->foreignId('component_variant_id')->nullable()->constrained('product_variants', 'id', 'combo_items_component_variant_id_fk')->nullOnDelete();

            $table->integer('sort_order')->default(0);
            $table->decimal('unit_price', 20, 6)->nullable();
            $table->decimal('estimated_service_time_hours', 8, 2)
                  ->nullable()
                  ->comment('Standard duration for labour operations');

            $table->enum('incentive_type', ['percentage', 'fixed'])
                ->default('fixed')
                ->nullable()
                ->comment('% of sales price, or a flat amount per unit');
            $table->decimal('incentive_value', 10, 6)->default(0);

            $table->decimal('quantity', 20, 6);
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'combo_items_uom_id_fk');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
