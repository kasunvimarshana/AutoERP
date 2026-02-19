<?php

declare(strict_types=1);

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
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('vehicle_number')->unique()->comment('Unique vehicle identifier');
            $table->string('registration_number')->unique()->comment('License plate number');
            $table->string('vin')->nullable()->unique()->comment('Vehicle Identification Number');
            $table->string('make')->comment('Vehicle manufacturer');
            $table->string('model')->comment('Vehicle model');
            $table->year('year');
            $table->string('color')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'lpg', 'cng'])->nullable();
            $table->enum('transmission', ['manual', 'automatic', 'cvt', 'dct'])->nullable();
            $table->integer('current_mileage')->default(0)->comment('Current odometer reading');
            $table->date('purchase_date')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->enum('status', ['active', 'inactive', 'sold', 'scrapped'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('last_service_date')->nullable();
            $table->integer('next_service_mileage')->nullable();
            $table->date('next_service_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('customer_id');
            $table->index('vehicle_number');
            $table->index('registration_number');
            $table->index('vin');
            $table->index('make');
            $table->index('model');
            $table->index('status');
            $table->index('created_at');
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
