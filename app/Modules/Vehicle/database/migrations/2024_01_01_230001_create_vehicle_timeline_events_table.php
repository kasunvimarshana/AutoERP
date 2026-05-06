<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_timeline_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->dateTime('occurred_at');
            $table->string('event_type');
            $table->string('event_subtype')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('revenue_amount', 20, 6)->default(0);
            $table->decimal('expense_amount', 20, 6)->default(0);
            $table->decimal('penalty_amount', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->unsignedInteger('distance_travelled_km')->nullable();
            $table->decimal('fuel_consumed_litre', 10, 2)->nullable();
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained('rental_bookings')->nullOnDelete();
            $table->foreignId('job_card_id')->nullable()->constrained('service_job_cards')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id','occurred_at'], 'timeline_vehicle_time_idx');
            $table->index(['tenant_id','event_type','occurred_at'], 'timeline_type_time_idx');
            $table->index(['tenant_id','booking_id'], 'timeline_booking_idx');
            $table->index(['tenant_id','job_card_id'], 'timeline_jobcard_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_timeline_events');
    }
};
