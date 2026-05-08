<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_driver_charge_configs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('employees');

            $table->decimal('daily_rate', 20, 6)->nullable();
            $table->decimal('overtime_hourly_rate', 20, 6)->nullable();
            $table->decimal('outstation_allowance', 20, 6)->nullable();
            $table->integer('free_daily_km')->default(0); // driver has own mileage allowance?

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_driver_charge_configs');
    }
};
