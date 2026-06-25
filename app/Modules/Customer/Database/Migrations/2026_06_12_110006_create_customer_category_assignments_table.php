<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_category_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('customer_id');
            $table->foreignId('customer_category_id');
            $table->timestamps();

            $table->unique(['customer_id', 'customer_category_id'], 'customer_category_assignments_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'customer_category_assignments_tenant_org_idx');

            $table->unique(['id', 'tenant_id'], 'customer_category_assignments_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'customer_category_assignments_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'customer_category_assignments_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();
            $table->foreign(['customer_category_id', 'tenant_id'], 'customer_category_assignments_customer_category_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customer_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_category_assignments');
    }
};
