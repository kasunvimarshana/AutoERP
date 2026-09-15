<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Enums\UsageChargeComponent;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_customer_usage_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('agreement_id');
            $table->foreignId('running_chart_id');
            $table->string('component', UsageChargeComponent::STORAGE_LENGTH);
            $table->date('period_from');
            $table->date('period_until');
            $table->decimal('amount', AgreementFields::DECIMAL_PRECISION, AgreementFields::DECIMAL_SCALE);
            $table->json('calculation');
            $table->unsignedBigInteger('row_version')->default(AgreementFields::INITIAL_VERSION);
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('actor_id');
            $table->timestamps();
            $table->foreign(['actor_id', 'tenant_id'], 'vruc_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['voided_by', 'tenant_id'], 'vruc_void_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->unique(['id', 'tenant_id'], 'vruc_tenant_uk');
            $table->index(['running_chart_id', 'component'], 'vruc_chart_component_ix');
            $table->foreign(['running_chart_id', 'tenant_id'], 'vruc_chart_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_running_charts')->restrictOnDelete();
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vruc_org_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'vruc_agreement_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_customer_agreements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_customer_usage_charges');
    }
};
