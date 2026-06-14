<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('replaces_agreement_vehicle_id')->nullable()
                ->constrained('rental_agreement_vehicles')->restrictOnDelete();
            $table->string('owner_party_type', 20)->nullable();
            $table->unsignedBigInteger('owner_party_id')->nullable();
            $table->dateTime('allocated_from');
            $table->dateTime('allocated_to')->nullable();
            $table->decimal('start_odometer', 20, 6);
            $table->decimal('end_odometer', 20, 6)->nullable();
            $table->string('status', 20)->default('allocated');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'rental_agreement_vehicles_tenant_org_idx');
            $table->index(['vehicle_id', 'allocated_from', 'allocated_to'], 'rental_agreement_vehicles_period_idx');
            $table->index(['agreement_id', 'status'], 'rental_agreement_vehicles_agreement_status_idx');
            $table->index('replaces_agreement_vehicle_id', 'rental_agreement_vehicles_replaces_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_vehicles');
    }
};
