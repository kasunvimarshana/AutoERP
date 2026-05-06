<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_booking_terms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();   // NULL = currently active
            $table->string('rental_rate_type');             // hourly,daily,weekly,monthly,mileage,fixed
            $table->decimal('rate_amount', 20, 6);
            $table->decimal('overtime_rate_amount', 20, 6)->nullable();
            $table->decimal('daily_mileage_allowance_km', 10, 2)->nullable();
            $table->decimal('excess_mileage_rate_per_km', 20, 6)->nullable();
            $table->boolean('driver_included')->default(false);
            $table->decimal('driver_daily_rate', 20, 6)->nullable();
            $table->decimal('driver_overtime_hourly_rate', 20, 6)->nullable();
            $table->decimal('driver_outstation_allowance', 20, 6)->nullable();
            $table->boolean('is_extension')->default(false);
            $table->foreignId('changed_by')->constrained('users');
            $table->string('change_reason')->nullable();
            $table->string('status')->default('active');    // active, superseded
            $table->timestamps();
            $table->index(['booking_id','effective_from'], 'booking_terms_effective_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_booking_terms');
    }
};
