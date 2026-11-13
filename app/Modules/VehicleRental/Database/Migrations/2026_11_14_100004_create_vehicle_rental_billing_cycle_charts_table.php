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
        Schema::create('vehicle_rental_billing_cycle_charts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('billing_cycle_id');
            $table->foreignId('running_chart_id');
            $table->foreignId('vehicle_id');
            $table->string('driver_name_snapshot')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->decimal('commercial_km', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->unsignedInteger('normal_ot_minutes')->default(0);
            $table->unsignedInteger('double_ot_minutes')->default(0);
            $table->unsignedInteger('night_outs')->default(0);
            $table->decimal('tolls_amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->default(0);
            $table->boolean('is_replacement')->default(false);
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vrbcc_tenant_uk');
            $table->unique(['billing_cycle_id', 'running_chart_id'], 'vrbcc_cycle_chart_uk');
            $table->index(['tenant_id', 'billing_cycle_id'], 'vrbcc_cycle_ix');
            $table->index(['running_chart_id', 'tenant_id'], 'vrbcc_chart_ix');

            $table->foreign(['billing_cycle_id', 'tenant_id'], 'vrbcc_cycle_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_billing_cycles')
                ->cascadeOnDelete();
            $table->foreign(['running_chart_id', 'tenant_id'], 'vrbcc_chart_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicle_rental_running_charts')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vrbcc_vehicle_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_billing_cycle_charts');
    }
};
