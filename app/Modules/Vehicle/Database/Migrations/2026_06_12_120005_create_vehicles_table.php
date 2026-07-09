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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicles_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('vehicle_number');
            $table->string('code')->nullable();
            $table->foreignId('vehicle_make_id')->nullable();
            $table->foreignId('vehicle_model_id')->nullable();
            $table->foreignId('vehicle_type_id')->nullable();
            $table->foreignId('vehicle_category_id')->nullable();
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
            $table->index(['tenant_id', 'organization_unit_id'], 'vehicles_tenant_org_ix');
            $table->index('status', 'vehicles_status_ix');

            $table->unique(['id', 'tenant_id'], 'vehicles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_make_id', 'tenant_id'], 'vehicles_vehicle_make_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_makes')
                ->restrictOnDelete();
            $table->foreign(['vehicle_model_id', 'tenant_id'], 'vehicles_vehicle_model_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_models')
                ->restrictOnDelete();
            $table->foreign(['vehicle_type_id', 'tenant_id'], 'vehicles_vehicle_type_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_types')
                ->restrictOnDelete();
            $table->foreign(['vehicle_category_id', 'tenant_id'], 'vehicles_vehicle_category_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_categories')
                ->restrictOnDelete();

            $table->foreign(['approved_by', 'tenant_id'], 'vehicles_approved_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
