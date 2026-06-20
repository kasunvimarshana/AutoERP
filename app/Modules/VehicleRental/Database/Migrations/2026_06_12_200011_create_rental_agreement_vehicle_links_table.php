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
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('inbound_agreement_id');
            $table->unsignedBigInteger('inbound_agreement_vehicle_id');
            $table->unsignedBigInteger('outbound_agreement_id');
            $table->unsignedBigInteger('outbound_agreement_vehicle_id');
            $table->dateTime('effective_from');
            $table->dateTime('effective_to');
            $table->string('status', 20)->default('draft');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('superseded_by_link_id')->nullable();
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
            $table->foreign('tenant_id', 'ravl_tenant_fk')
                ->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('organization_unit_id', 'ravl_org_fk')
                ->references('id')->on('organization_units')->restrictOnDelete();
            $table->foreign('vehicle_id', 'ravl_vehicle_fk')
                ->references('id')->on('vehicles')->restrictOnDelete();
            $table->foreign('inbound_agreement_id', 'ravl_in_agreement_fk')
                ->references('id')->on('rental_agreements')->restrictOnDelete();
            $table->foreign('inbound_agreement_vehicle_id', 'ravl_in_allocation_fk')
                ->references('id')->on('rental_agreement_vehicles')->restrictOnDelete();
            $table->foreign('outbound_agreement_id', 'ravl_out_agreement_fk')
                ->references('id')->on('rental_agreements')->restrictOnDelete();
            $table->foreign('outbound_agreement_vehicle_id', 'ravl_out_allocation_fk')
                ->references('id')->on('rental_agreement_vehicles')->restrictOnDelete();
            $table->foreign('superseded_by_link_id', 'rental_agreement_vehicle_links_superseded_fk')
                ->references('id')
                ->on('rental_agreement_vehicle_links')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreement_vehicle_links');
    }
};
