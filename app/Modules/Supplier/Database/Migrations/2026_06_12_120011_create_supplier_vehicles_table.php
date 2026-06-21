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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('relationship_type')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'current_guard'], 'supplier_vehicles_one_current_uk');
            $table->unique(['vehicle_id', 'supplier_id', 'active_guard'], 'supplier_vehicles_active_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'supplier_vehicles_scope_vehicle_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'supplier_id'], 'supplier_vehicles_scope_supplier_idx');
            $table->index(['vehicle_id', 'is_current'], 'supplier_vehicles_current_idx');
            $table->index(['started_at', 'ended_at'], 'supplier_vehicles_dates_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_vehicles');
    }
};
