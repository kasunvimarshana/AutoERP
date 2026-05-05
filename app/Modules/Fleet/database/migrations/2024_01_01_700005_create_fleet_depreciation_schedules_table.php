<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_depreciation_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles', 'id', 'fdep_veh_fk')->cascadeOnDelete();
            $table->enum('method', ['straight_line', 'declining_balance', 'units_of_production'])->default('straight_line');
            $table->integer('useful_life_months');
            $table->decimal('salvage_value', 20, 6)->default(0);
            $table->decimal('depreciable_amount', 20, 6);
            $table->decimal('monthly_depreciation_amount', 20, 6);
            $table->decimal('accumulated_depreciation', 20, 6)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vehicle_id'], 'fdep_veh_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_depreciation_schedules');
    }
};
