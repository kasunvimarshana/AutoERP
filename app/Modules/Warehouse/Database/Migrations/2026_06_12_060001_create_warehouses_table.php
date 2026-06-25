<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable();
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->string('code')->nullable();
            $table->string('image_path')->nullable();
            $table->string('type')->default('standard')->comment('standard, virtual, transit, quarantine');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id', 'name'], 'warehouses_scope_name_uk');
            $table->unique(['tenant_id', 'organization_unit_id', 'code'], 'warehouses_scope_code_uk');
            $table->index(['tenant_id', 'is_active'], 'warehouses_active_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'is_default'], 'warehouses_scope_default_idx');

            $table->unique(['id', 'tenant_id'], 'warehouses_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'warehouses_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
