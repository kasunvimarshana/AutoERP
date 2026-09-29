<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\VehicleUseStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_uses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('customer_agreement_id');
            $table->unsignedBigInteger('customer_agreement_version');
            $table->foreignId('owner_agreement_id')->nullable();
            $table->unsignedBigInteger('owner_agreement_version')->nullable();
            $table->foreignId('vehicle_id');
            $table->foreignId('replaces_use_id')->nullable();
            $table->string('vehicle_label_snapshot');
            $table->string('status')->default(VehicleUseStatus::Planned->value);
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('starts_at_input');
            $table->string('ends_at_input')->nullable();
            $table->dateTime('handed_over_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->decimal('handover_odometer', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->nullable();
            $table->decimal('return_odometer', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['id', 'tenant_id'], 'vru_tenant_uk');
            $table->unique('replaces_use_id', 'vru_replacement_uk');
            $table->foreign(['replaces_use_id', 'tenant_id'], 'vru_replacement_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_uses')->restrictOnDelete();
            $table->index(['tenant_id', 'vehicle_id', 'status', 'starts_at'], 'vru_timeline_ix');
            $table->index(['tenant_id', 'organization_unit_id', 'customer_agreement_id'], 'vru_customer_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vru_org_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['customer_agreement_id', 'tenant_id'], 'vru_customer_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_customer_agreements')->restrictOnDelete();
            $table->foreign(['customer_agreement_id', 'customer_agreement_version'], 'vru_customer_revision_fk')->references(['agreement_id', 'row_version'])->on('vehicle_rental_customer_agreements_history')->restrictOnDelete();
            $table->foreign(['owner_agreement_id', 'tenant_id'], 'vru_owner_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_owner_agreements')->restrictOnDelete();
            $table->foreign(['owner_agreement_id', 'owner_agreement_version'], 'vru_owner_revision_fk')->references(['agreement_id', 'row_version'])->on('vehicle_rental_owner_agreements_history')->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vru_vehicle_fk')->references(['id', 'tenant_id'])->on('vehicles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_uses');
    }
};
