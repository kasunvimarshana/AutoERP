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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->foreignId('current_customer_id')->constrained('customers')->cascadeOnDelete();
            
            // Vehicle Identification
            $table->string('vin')->unique()->comment('Vehicle Identification Number');
            $table->string('registration_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            
            // Vehicle Details
            $table->enum('vehicle_type', ['car', 'truck', 'motorcycle', 'suv', 'van', 'bus', 'other'])->default('car');
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->integer('engine_capacity')->nullable();
            
            // Meter Information
            $table->decimal('current_mileage', 10, 2)->default(0);
            $table->enum('mileage_unit', ['km', 'miles'])->default('km');
            $table->decimal('last_service_mileage', 10, 2)->nullable();
            $table->decimal('next_service_mileage', 10, 2)->nullable();
            
            // Service Schedule
            $table->timestamp('last_service_date')->nullable();
            $table->timestamp('next_service_date')->nullable();
            $table->integer('service_interval_days')->default(180);
            $table->integer('service_interval_mileage')->default(10000);
            
            // Insurance and Registration
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->date('insurance_expiry_date')->nullable();
            $table->date('registration_expiry_date')->nullable();
            
            // Additional Information
            $table->json('specifications')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'sold', 'written_off'])->default('active');
            
            // Ownership History
            $table->timestamp('ownership_start_date')->nullable();
            $table->integer('total_services')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('tenant_id');
            $table->index('current_customer_id');
            $table->index('vin');
            $table->index('registration_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
