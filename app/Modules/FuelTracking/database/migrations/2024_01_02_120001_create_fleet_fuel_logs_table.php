<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_fuel_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('org_unit_id')->index();
            $table->unsignedBigInteger('row_version')->default(1);

            $table->string('log_number', 64);
            $table->uuid('vehicle_id');
            $table->uuid('driver_id')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'lpg', 'other'])->default('petrol');
            $table->decimal('odometer_reading', 12, 2)->default(0);
            $table->decimal('litres', 20, 6)->default('0.000000');
            $table->decimal('cost_per_litre', 20, 6)->default('0.000000');
            $table->decimal('total_cost', 20, 6)->default('0.000000');
            $table->string('station_name', 255)->nullable();
            $table->timestamp('filled_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'org_unit_id', 'log_number'], 'fleet_fuel_logs_tenant_ou_number_uk');
            $table->index(['tenant_id', 'vehicle_id'], 'fleet_fuel_logs_tenant_vehicle_idx');
            $table->index(['tenant_id', 'filled_at'], 'fleet_fuel_logs_tenant_filled_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_fuel_logs');
    }
};
