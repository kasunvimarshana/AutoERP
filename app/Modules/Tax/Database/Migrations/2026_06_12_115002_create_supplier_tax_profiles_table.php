<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_tax_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'supplier_tax_profiles_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->foreignId('tax_group_id')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('exemption_status', 50)->default('taxable');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('supplier_id', 'supplier_tax_profiles_supplier_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'active'], 'supplier_tax_profiles_scope_active_ix');

            $table->unique(['id', 'tenant_id'], 'supplier_tax_profiles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_tax_profiles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_tax_profiles_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->cascadeOnDelete();
            $table->foreign(['tax_group_id', 'tenant_id'], 'supplier_tax_profiles_tax_group_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('tax_groups')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_tax_profiles');
    }
};
