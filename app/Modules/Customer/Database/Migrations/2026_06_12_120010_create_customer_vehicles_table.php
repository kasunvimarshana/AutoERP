<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('relationship_type')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedTinyInteger('current_guard')->nullable();
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'current_guard'], 'customer_vehicles_one_current_uk');
            $table->unique(['vehicle_id', 'customer_id', 'active_guard'], 'customer_vehicles_active_pair_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'vehicle_id'], 'customer_vehicles_scope_vehicle_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'customer_id'], 'customer_vehicles_scope_customer_idx');
            $table->index(['vehicle_id', 'is_current'], 'customer_vehicles_current_idx');
            $table->index(['started_at', 'ended_at'], 'customer_vehicles_dates_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('customer_vehicles');
    }
};
