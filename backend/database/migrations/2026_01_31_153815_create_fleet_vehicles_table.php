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
        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('vehicle_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('vin')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('color')->nullable();
            $table->integer('mileage')->default(0);
            $table->string('fuel_type')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance', 'retired'])->default('active');
            $table->date('last_service_date')->nullable();
            $table->date('next_service_due')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('vehicle_number');
            $table->index('status');
            $table->index('next_service_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicles');
    }
};
