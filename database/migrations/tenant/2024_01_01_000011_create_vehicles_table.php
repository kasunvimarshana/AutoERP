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
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('vin')->unique();
            $table->string('registration_number')->nullable();
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('color')->nullable();
            $table->string('engine_number')->nullable();
            $table->integer('odometer_reading')->default(0);
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'cng'])->nullable();
            $table->enum('transmission', ['manual', 'automatic', 'cvt'])->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'vin']);
        });

        Schema::create('vehicle_service_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('restrict');
            $table->date('service_date');
            $table->string('service_type');
            $table->integer('odometer_at_service');
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('performed_by')->nullable();
            $table->json('parts_used')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'service_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_history');
        Schema::dropIfExists('vehicles');
    }
};
