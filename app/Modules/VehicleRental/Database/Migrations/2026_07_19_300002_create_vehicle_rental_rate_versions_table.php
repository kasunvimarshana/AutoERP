<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_rate_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_rate_versions_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('agreement_id');
            $table->unsignedInteger('version_number');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default(RentalRateVersionStatus::Draft->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['agreement_id', 'version_number'], 'vr_rate_versions_agreement_version_uk');
            $table->unique(['id', 'tenant_id'], 'vr_rate_versions_id_tenant_uk');
            $table->index(['agreement_id', 'status', 'effective_from'], 'vr_rate_versions_agreement_status_ix');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_rate_versions_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['agreement_id', 'tenant_id'], 'vr_rate_versions_agreement_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_agreements')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'vr_rate_versions_created_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['activated_by', 'tenant_id'], 'vr_rate_versions_activated_by_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_rate_versions');
    }
};
