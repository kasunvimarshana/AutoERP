<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_identifiers')) {
            Schema::table('product_identifiers', function (Blueprint $table): void {
                $table->index(['tenant_id', 'is_active', 'value'], 'product_identifiers_tenant_active_value_idx');
                $table->index(['tenant_id', 'variant_id', 'is_active'], 'product_identifiers_tenant_variant_active_idx');
            });
        }

        if (Schema::hasTable('attribute_values')) {
            Schema::table('attribute_values', function (Blueprint $table): void {
                $table->index(['tenant_id', 'value'], 'attribute_values_tenant_value_idx');
            });
        }

        if (Schema::hasTable('variant_attribute_values')) {
            Schema::table('variant_attribute_values', function (Blueprint $table): void {
                $table->index(['tenant_id', 'attribute_value_id', 'variant_id'], 'variant_attr_values_tenant_attr_value_variant_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('variant_attribute_values')) {
            Schema::table('variant_attribute_values', function (Blueprint $table): void {
                $table->dropIndex('variant_attr_values_tenant_attr_value_variant_idx');
            });
        }

        if (Schema::hasTable('attribute_values')) {
            Schema::table('attribute_values', function (Blueprint $table): void {
                $table->dropIndex('attribute_values_tenant_value_idx');
            });
        }

        if (Schema::hasTable('product_identifiers')) {
            Schema::table('product_identifiers', function (Blueprint $table): void {
                $table->dropIndex('product_identifiers_tenant_active_value_idx');
                $table->dropIndex('product_identifiers_tenant_variant_active_idx');
            });
        }
    }
};
