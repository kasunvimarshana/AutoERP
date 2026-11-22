<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id');
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('tracks_mileage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'vehicle_service_job_types_tenant_code_uk');
            $table->unique(['id', 'tenant_id'], 'vehicle_service_job_types_id_tenant_uk');
            $table->index(['tenant_id', 'is_active', 'sort_order'], 'vehicle_service_job_types_tenant_active_sort_ix');

            $table->foreign('tenant_id', 'vehicle_service_job_types_tenant_fk')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_job_types_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();

            $table->foreign(['created_by', 'tenant_id'], 'vehicle_service_job_types_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(['updated_by', 'tenant_id'], 'vehicle_service_job_types_updated_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_types');
    }
};
