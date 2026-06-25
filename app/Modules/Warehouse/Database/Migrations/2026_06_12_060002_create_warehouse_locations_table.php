<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('warehouse_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->string('type')->default('bin')->comment('zone, aisle, rack, shelf, bin, staging, dispatch');               // zone, aisle, rack, shelf, bin, staging, dispatch
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pickable')->default(true);
            $table->boolean('is_receivable')->default(true);
            $table->boolean('is_default')->default(false);
            $table->decimal('capacity', 20, 6)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'warehouse_id', 'name'], 'warehouse_locations_warehouse_name_uk');
            $table->unique(['tenant_id', 'warehouse_id', 'code'], 'warehouse_locations_warehouse_code_uk');
            $table->index(['tenant_id', 'parent_id'], 'warehouse_locations_parent_idx');
            $table->index(['warehouse_id', 'is_default'], 'warehouse_locations_default_idx');
            $table->index(['warehouse_id', 'path'], 'warehouse_locations_path_idx');

            $table->unique(['id', 'tenant_id'], 'warehouse_locations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'warehouse_locations_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'warehouse_locations_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->cascadeOnDelete();
            $table->foreign(['parent_id', 'tenant_id'], 'warehouse_locations_parent_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
