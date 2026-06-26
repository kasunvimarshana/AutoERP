<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'supplier_vehicles_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('supplier_id');
            $table->foreignId('vehicle_id');
            $table->string('relationship_type')->nullable();
            $table->dateTime('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'current_guard'], 'supplier_vehicles_one_current_uk');
            $table->unique(['vehicle_id', 'supplier_id', 'active_guard'], 'supplier_vehicles_active_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'supplier_vehicles_scope_vehicle_ix');
            $table->index(['tenant_id', 'organization_unit_id', 'supplier_id'], 'supplier_vehicles_scope_supplier_ix');
            $table->index(['vehicle_id', 'is_current'], 'supplier_vehicles_current_ix');
            $table->index(['started_at', 'ended_at'], 'supplier_vehicles_dates_ix');

            $table->unique(['id', 'tenant_id'], 'supplier_vehicles_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'supplier_vehicles_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['supplier_id', 'tenant_id'], 'supplier_vehicles_supplier_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('suppliers')
                ->restrictOnDelete();
            $table->foreign(['vehicle_id', 'tenant_id'], 'supplier_vehicles_vehicle_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('vehicles')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_vehicles');
    }
};
