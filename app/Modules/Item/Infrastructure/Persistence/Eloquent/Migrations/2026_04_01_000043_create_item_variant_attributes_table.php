<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_variant_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('item_attributes')->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_variation_axis')->default(true);       // true = used to generate variants
            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'item_id', 'attribute_id'],
                'item_variant_attributes_item_attribute_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_variant_attributes');
    }
};
