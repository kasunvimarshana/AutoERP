<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_vehicle_state_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles', 'id', 'fvsl_veh_fk')->cascadeOnDelete();
            $table->string('from_state', 30);
            $table->string('to_state', 30);
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'vehicle_id'], 'fvsl_tenant_veh_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicle_state_logs');
    }
};
