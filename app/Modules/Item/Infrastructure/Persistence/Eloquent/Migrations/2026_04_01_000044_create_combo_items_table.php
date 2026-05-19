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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('combo_item_id')->constrained('items')->cascadeOnDelete()->comment('the bundle');
            $table->foreignId('component_item_id')->constrained('items')->cascadeOnDelete()->comment('the item inside the bundle');
            $table->foreignId('component_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->decimal('quantity', 20, 4);
            $table->foreignId('uom_id')->constrained('units_of_measure');
            $table->decimal('unit_price', 20, 4)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
