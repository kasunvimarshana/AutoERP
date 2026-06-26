<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_service_inspections_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->text('customer_complaint')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('recommended_work')->nullable();
            $table->decimal('odometer_reading', 20, 6)->nullable();
            $table->string('fuel_level')->nullable();
            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->unique('vehicle_service_job_id', 'vehicle_service_inspections_job_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_inspections_tenant_org_ix');

            $table->unique(['id', 'tenant_id'], 'vehicle_service_inspections_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_inspections_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_inspections_vehicle_service_job_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_inspections');
    }
};
