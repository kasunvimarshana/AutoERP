<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_service_job_discounts_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->unsignedBigInteger('revision');
            $table->string('action', 20);
            $table->string('calculation_type', 20);
            $table->decimal('rate', 20, 6)->default('0.000000');
            $table->decimal('fixed_amount', 20, 6)->default('0.000000');
            $table->decimal('calculation_base_snapshot', 20, 6);
            $table->decimal('calculated_amount_snapshot', 20, 6);
            $table->text('reason');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');

            $table->unique(['vehicle_service_job_id', 'revision'], 'vehicle_service_job_discounts_job_revision_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_job_discounts_tenant_org_ix');
            $table->index(['vehicle_service_job_id', 'changed_at'], 'vehicle_service_job_discounts_job_changed_ix');
            $table->unique(['id', 'tenant_id'], 'vehicle_service_job_discounts_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_job_discounts_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_job_discounts_job_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->restrictOnDelete();
            $table->foreign(['changed_by', 'tenant_id'], 'vehicle_service_job_discounts_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_discounts');
    }
};
