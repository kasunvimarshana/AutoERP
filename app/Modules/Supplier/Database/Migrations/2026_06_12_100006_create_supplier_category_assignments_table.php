<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_category_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->foreignId('supplier_category_id');
            $table->timestamps();

            $table->unique(['supplier_id', 'supplier_category_id'], 'supplier_category_assignments_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_category_assignments_tenant_org_idx');

            $table->unique(['id', 'tenant_id'], 'supplier_category_assignments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_category_assignments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_category_assignments_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->cascadeOnDelete();
            $table->foreign(['supplier_category_id', 'tenant_id'], 'supplier_category_assignments_supplier_category_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('supplier_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_category_assignments');
    }
};
