<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_bundles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'item_bundles_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('parent_item_id');
            $table->foreignId('child_item_id');
            $table->foreignId('child_variant_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->foreignId('uom_id')->nullable();
            $table->string('line_type', 30);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->boolean('uses_job_supervisor')->default(false);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_bundles_tenant_org_ix');
            $table->index('parent_item_id', 'item_bundles_parent_ix');
            $table->index('child_item_id', 'item_bundles_child_ix');

            $table->unique(['id', 'tenant_id'], 'item_bundles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_bundles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['parent_item_id', 'tenant_id'], 'item_bundles_parent_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
            $table->foreign(['child_item_id', 'tenant_id'], 'item_bundles_child_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['child_variant_id', 'tenant_id'], 'item_bundles_child_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'item_bundles_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_bundles');
    }
};
