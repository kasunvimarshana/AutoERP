<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(null, 'id', 'srr_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'srr_variant_id_fk')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained(null, 'id', 'srr_warehouse_id_fk')->cascadeOnDelete();
            $table->decimal('minimum_quantity', 20, 6)->default(0);
            $table->decimal('maximum_quantity', 20, 6)->nullable();
            $table->decimal('reorder_quantity', 20, 6);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'product_id', 'variant_id', 'warehouse_id'],
                'srr_tenant_product_variant_warehouse_uk'
            );
            $table->index(['tenant_id', 'warehouse_id'], 'srr_tenant_warehouse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reorder_rules');
    }
};
