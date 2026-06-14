<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->cascadeOnDelete();
            $table->foreignId('agreement_vehicle_id')->constrained('rental_agreement_vehicles')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->date('usage_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('working_minutes')->default(0);
            $table->decimal('start_odometer', 20, 6);
            $table->decimal('end_odometer', 20, 6);
            $table->decimal('distance_km', 20, 6);
            $table->decimal('cumulative_km', 20, 6)->nullable();
            $table->decimal('comparative_km', 20, 6)->nullable();
            $table->string('usage_fingerprint', 64);
            $table->text('odometer_variance_reason')->nullable();
            $table->string('trip_from')->nullable();
            $table->string('trip_to')->nullable();
            $table->string('trip_purpose')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'usage_fingerprint'], 'rental_usage_logs_fingerprint_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_usage_logs_tenant_org_idx');
            $table->index(['agreement_id', 'usage_date'], 'rental_usage_logs_agreement_date_idx');
            $table->index(['agreement_vehicle_id', 'usage_date'], 'rental_usage_logs_vehicle_date_idx');
            $table->index(['vehicle_id', 'usage_date', 'status'], 'rental_usage_logs_physical_vehicle_idx');
            $table->index(['driver_id', 'usage_date'], 'rental_usage_logs_driver_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_logs');
    }
};
