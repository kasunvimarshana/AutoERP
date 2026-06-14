<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreement_vehicle_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('inbound_agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('inbound_agreement_vehicle_id')->constrained('rental_agreement_vehicles')->restrictOnDelete();
            $table->foreignId('outbound_agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('outbound_agreement_vehicle_id')->constrained('rental_agreement_vehicles')->restrictOnDelete();
            $table->dateTime('effective_from');
            $table->dateTime('effective_to');
            $table->string('status', 20)->default('active');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique([
                'inbound_agreement_vehicle_id',
                'outbound_agreement_vehicle_id',
                'effective_from',
            ], 'rental_agreement_vehicle_links_pair_period_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_agreement_vehicle_links_tenant_org_idx');
            $table->index(['vehicle_id', 'effective_from', 'effective_to'], 'rental_agreement_vehicle_links_vehicle_period_idx');
            $table->index(['inbound_agreement_id', 'status'], 'rental_agreement_vehicle_links_inbound_idx');
            $table->index(['outbound_agreement_id', 'status'], 'rental_agreement_vehicle_links_outbound_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_vehicle_links');
    }
};
