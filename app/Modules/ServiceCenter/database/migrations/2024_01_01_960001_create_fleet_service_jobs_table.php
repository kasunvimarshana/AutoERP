<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_service_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles', 'id', 'fsjob_vehicle_fk');
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers', 'id', 'fsjob_driver_fk')->nullOnDelete();
            $table->string('job_number');
            $table->enum('job_type', ['maintenance', 'repair', 'inspection', 'cleaning', 'tyre', 'other'])->default('maintenance');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('scheduled_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->decimal('odometer_in', 12, 2)->nullable();
            $table->decimal('odometer_out', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->decimal('parts_cost', 20, 6)->default(0);
            $table->decimal('labour_cost', 20, 6)->default(0);
            $table->decimal('total_cost', 20, 6)->default(0);
            $table->text('technician_notes')->nullable();
            $table->boolean('customer_approval')->default(false);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'job_number'], 'fleet_service_jobs_tenant_ou_number_uk');
            $table->index(['tenant_id', 'status'], 'fleet_service_jobs_tenant_status_idx');
            $table->index(['tenant_id', 'vehicle_id'], 'fleet_service_jobs_tenant_vehicle_idx');
            $table->index(['tenant_id', 'scheduled_at'], 'fleet_service_jobs_tenant_scheduled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_service_jobs');
    }
};
