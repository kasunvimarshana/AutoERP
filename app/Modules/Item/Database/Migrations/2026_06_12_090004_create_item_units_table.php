<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'item_units_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('item_id');
            $table->foreignId('uom_id');
            $table->string('unit_role', 30);
            $table->decimal('conversion_factor', 20, 6)->default('1.000000');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'item_units_tenant_org_ix');
            $table->index('item_id', 'item_units_item_ix');
            $table->index('uom_id', 'item_units_uom_ix');
            $table->unique(['item_id', 'uom_id', 'unit_role'], 'item_units_item_uom_role_uk');

            $table->unique(['id', 'tenant_id'], 'item_units_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'item_units_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'item_units_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->cascadeOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'item_units_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
    }
};
