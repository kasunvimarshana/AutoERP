<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('vehicle_type_id')->constrained('fleet_vehicle_types', 'id', 'fveh_vtype_fk');
            $table->string('registration_number');
            $table->string('make');
            $table->string('model');
            $table->smallInteger('year');
            $table->string('color')->nullable();
            $table->string('vin_number')->nullable();
            $table->string('engine_number')->nullable();
            $table->enum('ownership_type', ['owned', 'third_party'])->default('owned');
            $table->foreignId('owner_supplier_id')->nullable()->constrained('suppliers', 'id', 'fveh_owner_fk')->nullOnDelete();
            $table->decimal('owner_commission_pct', 5, 2)->default(0)->comment('% of rental revenue paid to third-party owner');
            $table->boolean('is_rentable')->default(true);
            $table->boolean('is_serviceable')->default(true);
            $table->enum('current_state', ['available', 'rented', 'in_service', 'maintenance', 'retired'])->default('available');
            $table->decimal('current_odometer', 12, 2)->default(0);
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'lpg'])->default('petrol');
            $table->decimal('fuel_capacity', 8, 2)->nullable();
            $table->integer('seating_capacity')->default(5);
            $table->enum('transmission', ['manual', 'automatic', 'cvt'])->default('automatic');
            $table->foreignId('asset_account_id')->nullable()->constrained('accounts', 'id', 'fveh_asset_acc_fk')->nullOnDelete();
            $table->foreignId('accum_depreciation_account_id')->nullable()->constrained('accounts', 'id', 'fveh_depr_acc_fk')->nullOnDelete();
            $table->foreignId('depreciation_expense_account_id')->nullable()->constrained('accounts', 'id', 'fveh_depr_exp_fk')->nullOnDelete();
            $table->foreignId('rental_revenue_account_id')->nullable()->constrained('accounts', 'id', 'fveh_rent_rev_fk')->nullOnDelete();
            $table->foreignId('service_revenue_account_id')->nullable()->constrained('accounts', 'id', 'fveh_srv_rev_fk')->nullOnDelete();
            $table->decimal('acquisition_cost', 20, 6)->nullable();
            $table->date('acquired_at')->nullable();
            $table->date('disposed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'registration_number'], 'fleet_vehicles_tenant_ou_reg_uk');
            $table->index(['tenant_id', 'current_state'], 'fleet_vehicles_tenant_state_idx');
            $table->index(['tenant_id', 'is_rentable', 'current_state'], 'fleet_vehicles_rentable_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicles');
    }
};
