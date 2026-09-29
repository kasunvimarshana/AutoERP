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
        Schema::create('vehicle_rental_owner_base_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id');
            $table->foreignId('agreement_id');
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
            $table->foreign(['actor_id', 'tenant_id'], 'vroc_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['voided_by', 'tenant_id'], 'vroc_void_actor_fk')->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->unique(['id', 'tenant_id'], 'vroc_tenant_uk');
            $table->index(['agreement_id', 'period_from', 'period_until'], 'vroc_period_ix');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vroc_org_fk')->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'vroc_agreement_fk')->references(['id', 'tenant_id'])->on('vehicle_rental_owner_agreements')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_owner_base_charges');
    }
};
