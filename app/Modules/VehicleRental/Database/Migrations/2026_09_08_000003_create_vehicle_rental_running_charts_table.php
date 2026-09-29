<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\RunningChartStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_running_charts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('vehicle_use_id');
            $table->unsignedBigInteger('vehicle_use_version');
            $table->string('reference', AgreementFields::REFERENCE_LENGTH);
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->string('status')->default(RunningChartStatus::Draft->value);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('starts_at_input');
            $table->string('ends_at_input');
            $table->decimal('start_odometer', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->nullable();
            $table->decimal('end_odometer', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->nullable();
            $table->decimal('garage_km', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->nullable();
            $table->decimal('commercial_km', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->nullable();
            $table->string('ac_mode')->nullable();
            $table->unsignedInteger('normal_ot_minutes')->nullable();
            $table->unsignedInteger('double_ot_minutes')->nullable();
            $table->unsignedInteger('triple_ot_minutes')->nullable();
            $table->unsignedInteger('night_outs')->nullable();
            $table->text('driver_observation')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('corrects_chart_id')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->dateTime('reversed_at')->nullable();
            $table->timestamps();
            $table->unique(['id', 'tenant_id'], 'vrc_tenant_uk');
            $table->unique(['tenant_id', 'reference'], 'vrc_reference_uk');
            $table->unique('corrects_chart_id', 'vrc_correction_uk');
            $table->index(['tenant_id', 'vehicle_use_id', 'starts_at'], 'vrc_period_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vrc_org_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['vehicle_use_id', 'tenant_id'], 'vrc_use_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_uses')->restrictOnDelete();
            $table->foreign(['vehicle_use_id', 'vehicle_use_version'], 'vrc_revision_fk')->references(['vehicle_use_id', 'row_version'])->on('vehicle_rental_use_history')->restrictOnDelete();
            $table->foreign(['corrects_chart_id', 'tenant_id'], 'vrc_correction_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_running_charts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_running_charts');
    }
};
