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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->string('vehicle_number');
            $table->string('code')->nullable();
            $table->foreignId('vehicle_make_id')->nullable()->constrained('vehicle_makes')->nullOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('vehicle_types')->nullOnDelete();
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('current_owner_type')->nullable();
            $table->unsignedBigInteger('current_owner_id')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('vin_number')->nullable();
            $table->unsignedSmallInteger('manufacture_year')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('color')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('transmission_type')->nullable();
            $table->decimal('odometer_reading', 20, 6)->default('0.000000');
            $table->string('odometer_unit')->nullable();
            $table->string('fuel_level')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'vehicle_number'], 'vehicles_tenant_number_uk');
            $table->unique(['tenant_id', 'code'], 'vehicles_tenant_code_uk');
            $table->unique(['tenant_id', 'registration_number'], 'vehicles_tenant_registration_uk');
            $table->unique(['tenant_id', 'chassis_number'], 'vehicles_tenant_chassis_uk');
            $table->unique(['tenant_id', 'engine_number'], 'vehicles_tenant_engine_uk');
            $table->unique(['tenant_id', 'vin_number'], 'vehicles_tenant_vin_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicles_tenant_org_idx');
            $table->index('status', 'vehicles_status_idx');
            $table->index('customer_id', 'vehicles_customer_idx');
            $table->index(['current_owner_type', 'current_owner_id'], 'vehicles_owner_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
