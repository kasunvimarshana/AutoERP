<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_usage_contexts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->unsignedBigInteger('usage_log_id');
            $table->unsignedBigInteger('agreement_id');
            $table->unsignedBigInteger('agreement_vehicle_id');
            $table->unsignedBigInteger('agreement_vehicle_link_id')->nullable();
            $table->unsignedBigInteger('rate_snapshot_id');
            $table->string('agreement_direction', 20);
            $table->string('financial_side', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->timestamps();

            $table->unique(['usage_log_id', 'agreement_id'], 'rental_usage_contexts_log_agreement_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_usage_contexts_tenant_org_idx');
            $table->index(['agreement_id', 'usage_log_id'], 'rental_usage_contexts_agreement_log_idx');
            $table->index(['agreement_vehicle_link_id', 'usage_log_id'], 'rental_usage_contexts_link_log_idx');
            $table->foreign('tenant_id', 'ruc_tenant_fk')
                ->references('id')->on('tenants')->restrictOnDelete();
            $table->foreign('organization_unit_id', 'ruc_org_fk')
                ->references('id')->on('organization_units')->restrictOnDelete();
            $table->foreign('usage_log_id', 'ruc_usage_log_fk')
                ->references('id')->on('rental_usage_logs')->restrictOnDelete();
            $table->foreign('agreement_id', 'ruc_agreement_fk')
                ->references('id')->on('rental_agreements')->restrictOnDelete();
            $table->foreign('agreement_vehicle_id', 'ruc_allocation_fk')
                ->references('id')->on('rental_agreement_vehicles')->restrictOnDelete();
            $table->foreign('agreement_vehicle_link_id', 'ruc_vehicle_link_fk')
                ->references('id')->on('rental_agreement_vehicle_links')->restrictOnDelete();
            $table->foreign('rate_snapshot_id', 'ruc_rate_snapshot_fk')
                ->references('id')->on('rental_agreement_rate_snapshots')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_usage_contexts');
    }
};
