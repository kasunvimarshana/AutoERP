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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('registration_number');
            $table->string('vin');
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->integer('odometer_reading')->default(0);
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid'])->nullable();
            $table->enum('transmission', ['manual', 'automatic'])->nullable();
            $table->date('warranty_expires_at')->nullable();
            $table->date('insurance_expires_at')->nullable();
            $table->enum('status', ['active', 'sold', 'scrapped'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'registration_number']);
            $table->unique(['tenant_id', 'vin']);
            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id']);
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
