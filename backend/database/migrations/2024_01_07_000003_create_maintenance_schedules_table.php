<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fleet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('schedule_name');
            $table->text('description')->nullable();
            $table->enum('schedule_type', ['mileage_based', 'time_based', 'both'])->default('both');
            $table->integer('mileage_interval')->nullable(); // km/miles
            $table->integer('time_interval_days')->nullable();
            $table->date('last_service_date')->nullable();
            $table->integer('last_service_mileage')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('next_service_mileage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'is_active']);
            $table->index(['next_service_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
