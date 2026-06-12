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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->decimal('credit_limit', 20, 6)->default('0.000000');
            $table->integer('credit_period_days')->nullable();
            $table->decimal('warning_threshold_percent', 20, 6)->default('80.000000');
            $table->boolean('allow_over_credit')->default(false);
            $table->boolean('allow_partial_payment')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'customer_credit_profiles_tenant_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_profiles');
    }
};
