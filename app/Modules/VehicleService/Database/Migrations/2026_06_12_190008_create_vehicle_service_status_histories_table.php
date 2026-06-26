<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_service_job_id');
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_status_histories_tenant_org_idx');
            $table->index(['vehicle_service_job_id', 'changed_at'], 'vehicle_service_status_histories_job_changed_idx');

            $table->unique(['id', 'tenant_id'], 'vehicle_service_status_histories_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_service_status_histories_organization_un_61382dac_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_service_job_id', 'tenant_id'], 'vehicle_service_status_histories_vehicle_service_66403583_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_service_jobs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_status_histories');
    }
};
