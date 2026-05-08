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

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('vehicle_code')->nullable();
            $table->string('vin')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('make');
            $table->string('model');
            $table->year('year');
            $table->string('color')->nullable();
            $table->string('category');

            $table->string('usage_profile')->default('dual')->comment('rent_only, service_only, dual, internal');
            $table->string('current_status')->default('available')->comment('available, rented, in_service, reserved, decommissioned');

            // Operational data
            $table->unsignedBigInteger('current_odometer')->default(0);
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->unsignedTinyInteger('seating_capacity')->nullable();

            // $table->string('owner_type')->nullable()
            //       ->comment('Polymorphic owner: customer, supplier, partner, org_unit, employee');
            // $table->unsignedBigInteger('owner_id')->nullable();

            $table->date('registration_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('last_service_date')->nullable();
            $table->unsignedBigInteger('last_service_odometer')->nullable();
            $table->date('next_service_due_date')->nullable();
            $table->unsignedBigInteger('next_service_due_odometer')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id','org_unit_id','vehicle_code'], 'vehicles_code_uk');
            $table->unique(['tenant_id','org_unit_id','vin'], 'vehicles_vin_uk');
            $table->index(['tenant_id','current_status'], 'vehicles_status_idx');
            $table->index(['tenant_id','usage_profile'], 'vehicles_usage_idx');
            $table->index(['tenant_id','category'], 'vehicles_category_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
