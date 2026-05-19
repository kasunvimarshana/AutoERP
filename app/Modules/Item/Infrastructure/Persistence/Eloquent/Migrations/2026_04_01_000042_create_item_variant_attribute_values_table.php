<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('variant_id')->constrained('item_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();

            $table->unique(['tenant_id', 'variant_id',  'attribute_value_id'], 'item_variant_attribute_values_pk');
            $table->index(['tenant_id', 'variant_id'], 'item_variant_attribute_values_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_variant_attribute_values');
    }
};
