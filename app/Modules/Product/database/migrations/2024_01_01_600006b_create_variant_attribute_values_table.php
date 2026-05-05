<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('variant_id')->constrained('product_variants', 'id', 'variant_attribute_values_variant_id_fk')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained(null, 'id', 'variant_attribute_values_attribute_value_id_fk')->cascadeOnDelete();
            $table->primary(['variant_id', 'attribute_value_id'], 'variant_attribute_values_variant_attr_pk');
            $table->index(['tenant_id', 'variant_id'], 'variant_attribute_values_tenant_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};
