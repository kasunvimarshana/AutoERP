<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->string('address_type');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_addresses_tenant_org_idx');
            $table->index('supplier_id', 'supplier_addresses_supplier_idx');
            $table->index('address_type', 'supplier_addresses_type_idx');

            $table->unique(['id', 'tenant_id'], 'supplier_addresses_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_addresses_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_addresses_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_addresses');
    }
};
