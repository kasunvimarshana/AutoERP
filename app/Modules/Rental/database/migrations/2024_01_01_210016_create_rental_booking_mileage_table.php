<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_booking_mileage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();

            $table->unsignedBigInteger('opening_odometer');
            $table->unsignedBigInteger('closing_odometer')->nullable();
            $table->decimal('total_distance_km', 10, 2)->nullable();

            $table->decimal('allowed_distance_km', 10, 2)->nullable();   // from policy
            $table->decimal('excess_distance_km', 10, 2)->nullable();    // distance over allowance
            $table->decimal('excess_charge_rate', 20, 6)->nullable();
            $table->decimal('excess_charge_amount', 20, 6)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_booking_mileage');
    }
};
