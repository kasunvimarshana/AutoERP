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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_service_job_id')->constrained('vehicle_service_jobs')->cascadeOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_service_status_histories_tenant_org_idx');
            $table->index(['vehicle_service_job_id', 'changed_at'], 'vehicle_service_status_histories_job_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_status_histories');
    }
};
