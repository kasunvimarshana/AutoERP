<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->enum('ownership_type', ['company_owned', 'third_party_owned', 'customer_owned', 'leased']);
            $table->string('asset_code')->nullable();
            $table->string('make', 120);
            $table->string('model', 120);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('vin', 64)->nullable();
            $table->string('registration_number', 64)->nullable();
            $table->string('chassis_number', 64)->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric', 'cng', 'lpg', 'other'])->default('petrol');
            $table->enum('transmission', ['manual', 'automatic', 'cvt', 'semi_automatic', 'other'])->default('manual');
            $table->decimal('odometer', 20, 6)->default('0.000000');

            $table->enum('rental_status', ['available', 'reserved', 'rented', 'blocked'])->default('available');
            $table->enum('service_status', ['none', 'in_maintenance', 'under_repair', 'awaiting_parts', 'quality_check', 'ready_for_pickup', 'returned_to_fleet'])->default('none');
            $table->timestamp('next_maintenance_due_at')->nullable();

            $table->string('primary_image_path', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'vin'], 'vehicles_tenant_org_vin_uk');
            $table->unique(['tenant_id', 'org_unit_id', 'registration_number'], 'vehicles_tenant_org_registration_uk');
            $table->unique(['tenant_id', 'org_unit_id', 'chassis_number'], 'vehicles_tenant_org_chassis_uk');
            $table->index(['tenant_id', 'ownership_type'], 'vehicles_tenant_ownership_idx');
            $table->index(['tenant_id', 'rental_status', 'service_status'], 'vehicles_tenant_status_idx');
            $table->index(['tenant_id', 'next_maintenance_due_at'], 'vehicles_tenant_maintenance_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
