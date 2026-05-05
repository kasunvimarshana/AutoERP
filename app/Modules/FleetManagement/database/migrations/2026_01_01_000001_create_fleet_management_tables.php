<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('category_id');
            $table->string('make', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');
            $table->string('color', 50);
            $table->string('vin', 50)->unique();
            $table->string('plate_number', 20);
            $table->enum('ownership_type', ['company_owned', 'third_party', 'leased', 'customer_owned'])->default('company_owned');
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'lpg'])->default('petrol');
            $table->decimal('acquisition_cost', 20, 6)->default(0);
            $table->date('acquisition_date')->nullable();
            $table->unsignedBigInteger('third_party_owner_id')->nullable();
            $table->date('lease_end_date')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('registration_expiry')->nullable();
            $table->date('fitness_expiry')->nullable();
            $table->unsignedInteger('current_odometer')->default(0);
            $table->enum('rental_status', ['available', 'rented', 'reserved', 'maintenance'])->default('available');
            $table->enum('service_status', ['in_workshop', 'released'])->default('released');
            $table->enum('status', ['active', 'inactive', 'disposed'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('vehicle_categories');
            $table->unique(['tenant_id', 'plate_number']);
            $table->index(['tenant_id', 'rental_status']);
            $table->index(['tenant_id', 'service_status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['insurance_expiry']);
            $table->index(['registration_expiry']);
            $table->index(['fitness_expiry']);
        });

        Schema::create('vehicle_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->enum('doc_type', ['insurance', 'registration', 'fitness', 'roadworthy', 'permit', 'other']);
            $table->string('doc_number', 100);
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority', 200)->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_id')->references('id')->on('vehicles');
            $table->index(['vehicle_id', 'doc_type']);
            $table->index(['expiry_date', 'status']);
        });

        Schema::create('vehicle_fuel_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->date('log_date');
            $table->unsignedInteger('odometer');
            $table->decimal('liters', 10, 6);
            $table->decimal('cost_per_liter', 20, 6);
            $table->decimal('total_cost', 20, 6);
            $table->string('station', 200)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles');
            $table->index(['vehicle_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_fuel_logs');
        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_categories');
    }
};
