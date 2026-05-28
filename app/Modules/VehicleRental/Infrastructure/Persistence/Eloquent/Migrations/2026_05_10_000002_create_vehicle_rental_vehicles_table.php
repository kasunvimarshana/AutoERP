<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();

            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('default_driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('source_type')->comment('company_owned, external_provider, replacement, customer_assigned');
            $table->string('availability_status')->default('available');
            $table->string('rental_status')->default('available');
            $table->string('maintenance_status')->default('clear');
            $table->string('internal_code')->nullable();
            $table->string('provider_vehicle_reference')->nullable();
            $table->string('display_name');
            $table->string('registration_number')->nullable();
            $table->string('make_model')->nullable();
            $table->string('ownership_reference')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('registration_expiry_date')->nullable();
            $table->date('permit_expiry_date')->nullable();
            $table->dateTime('blocked_from')->nullable();
            $table->dateTime('blocked_to')->nullable();
            $table->text('block_reason')->nullable();
            $table->boolean('supports_with_driver')->default(true);
            $table->boolean('supports_without_driver')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'internal_code'], 'vehicle_rental_vehicles_code_uk');
            $table->index(['tenant_id', 'vehicle_id'], 'vehicle_rental_vehicles_vehicle_idx');
            $table->index(['tenant_id', 'source_type'], 'vehicle_rental_vehicles_source_idx');
            $table->index(['tenant_id', 'availability_status'], 'vehicle_rental_vehicles_availability_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_vehicles');
    }
};
