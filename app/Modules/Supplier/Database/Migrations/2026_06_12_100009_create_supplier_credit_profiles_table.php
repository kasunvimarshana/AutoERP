<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_credit_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id')->unique();
            $table->decimal('credit_limit', 20, 6)->default('0.000000');
            $table->integer('credit_period_days')->nullable();
            $table->decimal('warning_threshold_percent', 20, 6)->default('80.000000');
            $table->boolean('allow_over_credit')->default(false);
            $table->boolean('allow_partial_payment')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'supplier_credit_profiles_tenant_org_idx');

            $table->unique(['id', 'tenant_id'], 'supplier_credit_profiles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_credit_profiles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_credit_profiles_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_profiles');
    }
};
