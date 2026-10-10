<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\IncidentFields;
use Modules\VehicleRental\Enums\IncidentStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('vehicle_use_id');
            $table->foreignId('running_chart_id')->nullable();
            $table->string('reference', AgreementFields::REFERENCE_LENGTH);
            $table->string('incident_type', IncidentFields::TYPE_LENGTH);
            $table->date('occurred_on');
            $table->string('evidence_reference', IncidentFields::EVIDENCE_REFERENCE_LENGTH);
            $table->text('description');
            $table->string('status', IncidentFields::STATUS_LENGTH)->default(IncidentStatus::Recorded->value);
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->foreignId('created_by');
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'vri_tenant_uk');
            $table->unique(['tenant_id', 'reference'], 'vri_reference_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_use_id', 'occurred_on'], 'vri_use_date_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vri_org_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['vehicle_use_id', 'tenant_id'], 'vri_use_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_uses')->restrictOnDelete();
            $table->foreign(['running_chart_id', 'tenant_id'], 'vri_chart_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_running_charts')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vri_created_by_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['reviewed_by', 'tenant_id'], 'vri_reviewed_by_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_incidents');
    }
};
