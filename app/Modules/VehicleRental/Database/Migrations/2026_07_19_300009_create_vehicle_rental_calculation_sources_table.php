<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_calculation_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vr_calculation_sources_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('calculation_id');
            $table->foreignId('running_chart_id');
            $table->string('side', 20);
            $table->boolean('active_marker')->nullable()->default(true);
            $table->timestamps();

            $table->unique(['calculation_id', 'running_chart_id'], 'vr_calculation_sources_calculation_chart_uk');
            $table->unique(['running_chart_id', 'side', 'active_marker'], 'vr_calculation_sources_chart_side_active_uk');
            $table->unique(['id', 'tenant_id'], 'vr_calculation_sources_id_tenant_uk');

            $table->foreign(['organization_unit_id', 'tenant_id'], 'vr_calculation_sources_org_tenant_fk')
                ->references(['id', 'tenant_id'])->on('organization_units')->restrictOnDelete();
            $table->foreign(['calculation_id', 'tenant_id'], 'vr_calculation_sources_calculation_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_calculations')->restrictOnDelete();
            $table->foreign(['running_chart_id', 'tenant_id'], 'vr_calculation_sources_chart_tenant_fk')
                ->references(['id', 'tenant_id'])->on('vehicle_rental_running_charts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_calculation_sources');
    }
};
