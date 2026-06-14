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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('usage_log_id')->constrained('rental_usage_logs')->cascadeOnDelete();
            $table->foreignId('agreement_id')->constrained('rental_agreements')->restrictOnDelete();
            $table->foreignId('agreement_vehicle_id')->constrained('rental_agreement_vehicles')->restrictOnDelete();
            $table->foreignId('agreement_vehicle_link_id')->nullable()
                ->constrained('rental_agreement_vehicle_links')->restrictOnDelete();
            $table->foreignId('rate_snapshot_id')->constrained('rental_agreement_rate_snapshots')->restrictOnDelete();
            $table->string('agreement_direction', 20);
            $table->string('financial_side', 20);
            $table->string('party_type', 20);
            $table->unsignedBigInteger('party_id');
            $table->timestamps();

            $table->unique(['usage_log_id', 'agreement_id'], 'rental_usage_contexts_log_agreement_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'rental_usage_contexts_tenant_org_idx');
            $table->index(['agreement_id', 'usage_log_id'], 'rental_usage_contexts_agreement_log_idx');
            $table->index(['agreement_vehicle_link_id', 'usage_log_id'], 'rental_usage_contexts_link_log_idx');
        });

        Schema::table('rental_charge_calculations', function (Blueprint $table): void {
            $table->foreign('usage_context_id', 'rental_charge_calculations_usage_context_fk')
                ->references('id')
                ->on('rental_usage_contexts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rental_charge_calculations', function (Blueprint $table): void {
            $table->dropForeign('rental_charge_calculations_usage_context_fk');
        });
        Schema::dropIfExists('rental_usage_contexts');
    }
};
