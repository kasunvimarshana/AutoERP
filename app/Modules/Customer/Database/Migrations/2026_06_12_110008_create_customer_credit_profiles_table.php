<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'customer_credit_profiles_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('customer_id')->unique('customer_credit_profiles_cust_uk');
            $table->decimal('credit_limit', 20, 6)->default('0.000000');
            $table->integer('credit_period_days')->nullable();
            $table->decimal('warning_threshold_percent', 20, 6)->default('80.000000');
            $table->boolean('allow_over_credit')->default(false);
            $table->boolean('allow_partial_payment')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'customer_credit_profiles_tenant_org_ix');

            $table->unique(['id', 'tenant_id'], 'customer_credit_profiles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'customer_credit_profiles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['customer_id', 'tenant_id'], 'customer_credit_profiles_customer_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('customers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_profiles');
    }
};
