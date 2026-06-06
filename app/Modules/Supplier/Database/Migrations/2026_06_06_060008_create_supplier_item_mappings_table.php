<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_item_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->string('supplier_item_code')->nullable();
            $table->string('supplier_item_name')->nullable();
            $table->foreignId('default_purchase_uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->decimal('minimum_order_quantity', 20, 6)->default('0.000000');
            $table->integer('lead_time_days')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_item_mappings_tenant_org_idx');
            $table->index('supplier_id', 'supplier_item_mappings_supplier_idx');
            $table->index('item_id', 'supplier_item_mappings_item_idx');
            $table->index('item_variant_id', 'supplier_item_mappings_variant_idx');
            $table->index('supplier_item_code', 'supplier_item_mappings_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_item_mappings');
    }
};
