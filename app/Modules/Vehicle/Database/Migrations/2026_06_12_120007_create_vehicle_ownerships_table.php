<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'vehicle_ownerships_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('vehicle_id');
            $table->string('owner_type')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('ownership_type');
            $table->dateTime('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'vehicle_ownerships_tenant_org_ix');
            $table->index('vehicle_id', 'vehicle_ownerships_vehicle_ix');
            $table->index(['owner_type', 'owner_id'], 'vehicle_ownerships_owner_ix');
            $table->index(['vehicle_id', 'is_current'], 'vehicle_ownerships_current_ix');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id', 'owner_type', 'is_current'], 'vehicle_ownerships_current_owner_ix');

            $table->unique(['id', 'tenant_id'], 'vehicle_ownerships_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'vehicle_ownerships_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'vehicle_ownerships_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_ownerships');
    }
};
