<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_mileage_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('booking_id')->constrained('rental_bookings')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->unsignedBigInteger('odometer_reading');
            $table->foreignId('recorded_by')->constrained('users');
            $table->string('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['booking_id','recorded_at'], 'mileage_log_booking_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_mileage_log');
    }
};
