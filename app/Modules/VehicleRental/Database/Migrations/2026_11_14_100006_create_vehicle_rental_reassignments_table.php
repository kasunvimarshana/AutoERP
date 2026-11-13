<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_reassignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('reassignment_type', 32);
            $table->foreignId('customer_agreement_id')->nullable();
            $table->foreignId('vehicle_use_id')->nullable();
            $table->foreignId('running_chart_id')->nullable();
            $table->foreignId('previous_vehicle_id')->nullable();
            $table->foreignId('new_vehicle_id')->nullable();
            $table->foreignId('previous_owner_agreement_id')->nullable();
            $table->foreignId('new_owner_agreement_id')->nullable();
            $table->foreignId('previous_driver_employee_id')->nullable();
            $table->foreignId('new_driver_employee_id')->nullable();
            $table->string('financial_status', 32)->default('pending');
            $table->decimal('financial_delta_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->text('reason');
            $table->dateTime('occurred_at');
            $table->foreignId('user_id')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vrr_tenant_uk');
            $table->index(['tenant_id', 'reassignment_type'], 'vrr_type_ix');
            $table->index(['customer_agreement_id', 'tenant_id'], 'vrr_cust_ix');
            $table->index(['vehicle_use_id', 'tenant_id'], 'vrr_use_ix');
            $table->index(['running_chart_id', 'tenant_id'], 'vrr_chart_ix');

            $table->foreign(['customer_agreement_id', 'tenant_id'], 'vrr_cust_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_customer_agreements')
                ->restrictOnDelete();
            $table->foreign(['vehicle_use_id', 'tenant_id'], 'vrr_use_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_uses')
                ->restrictOnDelete();
            $table->foreign(['running_chart_id', 'tenant_id'], 'vrr_chart_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_running_charts')
                ->restrictOnDelete();
            $table->foreign(['previous_vehicle_id', 'tenant_id'], 'vrr_prev_veh_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['new_vehicle_id', 'tenant_id'], 'vrr_new_veh_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
            $table->foreign(['previous_owner_agreement_id', 'tenant_id'], 'vrr_prev_own_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_owner_agreements')
                ->restrictOnDelete();
            $table->foreign(['new_owner_agreement_id', 'tenant_id'], 'vrr_new_own_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_owner_agreements')
                ->restrictOnDelete();
            $table->foreign(['previous_driver_employee_id', 'tenant_id'], 'vrr_prev_drv_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['new_driver_employee_id', 'tenant_id'], 'vrr_new_drv_fk')
                ->references(['id', 'tenant_id'])
                ->on('hr_employees')
                ->restrictOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'vrr_user_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_reassignments');
    }
};
