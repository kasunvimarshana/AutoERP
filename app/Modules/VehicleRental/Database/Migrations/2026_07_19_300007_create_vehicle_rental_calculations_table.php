<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\RentalCalculationStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_calculations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_calculations_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->string('calculation_number', 100);
            $table->foreignId('agreement_id');
            $table->foreignId('rate_version_id');
            $table->foreignId('currency_id');
            $table->string('side', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('chart_count');
            $table->unsignedInteger('operating_days');
            $table->decimal('commercial_km', 20, 6)->nullable();
            $table->decimal('included_km', 20, 6);
            $table->decimal('excess_km', 20, 6)->nullable();
            $table->decimal('subtotal_amount', 20, 6);
            $table->string('status', 20)->default(RentalCalculationStatus::Calculated->value);
            $table->boolean('active_marker')->nullable()->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'calculation_number'], 'vr_calculations_tenant_number_uk');
            $table->unique(['id', 'tenant_id'], 'vr_calculations_id_tenant_uk');
            $table->unique(['agreement_id', 'side', 'period_start', 'period_end', 'active_marker'], 'vr_calculations_period_active_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'side', 'status', 'period_start'], 'vr_calculations_scope_side_status_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_calculations_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'vr_calculations_agreement_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_agreements')->restrictOnDelete();
            $table->foreign(['rate_version_id', 'tenant_id'], 'vr_calculations_rate_version_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_rate_versions')->restrictOnDelete();
            $table->foreign('currency_id', 'vr_calculations_currency_fk')
                ->references('id')->on('currencies')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_calculations_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['cancelled_by', 'tenant_id'], 'vr_calculations_cancelled_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_calculations');
    }
};
