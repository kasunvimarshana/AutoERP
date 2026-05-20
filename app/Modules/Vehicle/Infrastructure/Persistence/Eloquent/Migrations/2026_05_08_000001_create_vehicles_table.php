<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('vehicle_code', 50)->nullable();
            $table->string('vin', 50)->nullable();
            $table->string('license_plate', 30)->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->year('year')->nullable();
            $table->string('color', 30)->nullable();
            $table->string('category', 50)->nullable();
            $table->string('usage_profile')->default('dual')->comment('rent_only, service_only, dual, internal');
            $table->string('fuel_type', 30)->nullable();
            $table->string('transmission', 30)->nullable();
            $table->unsignedTinyInteger('seating_capacity')->nullable();
            $table->unsignedBigInteger('current_odometer')->default(0);
            $table->string('status')->default('active')->comment('active, inactive');
            $table->date('registration_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('last_service_date')->nullable();
            $table->unsignedBigInteger('last_service_odometer')->nullable();
            $table->date('next_service_due_date')->nullable();
            $table->unsignedBigInteger('next_service_due_odometer')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'vehicle_code'], 'vehicles_vehicle_code_uk');
            $table->unique(['tenant_id', 'vin'], 'vehicles_vin_uk');
            $table->index(['tenant_id', 'status'], 'vehicles_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
