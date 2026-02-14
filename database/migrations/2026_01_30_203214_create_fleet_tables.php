<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create vehicles table
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->string('registration_number')->unique();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('vin')->nullable()->unique();
            $table->enum('fuel_type', ['petrol', 'diesel', 'electric', 'hybrid', 'cng', 'lpg'])->default('petrol');
            $table->string('color')->nullable();
            $table->enum('status', ['active', 'maintenance', 'retired', 'sold'])->default('active');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->unsignedInteger('current_mileage')->default(0);
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'branch_id']);
            $table->index('registration_number');
            $table->index('vin');
        });

        // Create maintenance_records table
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->enum('type', ['routine', 'repair', 'inspection', 'tire_change', 'oil_change', 'other'])->default('routine');
            $table->text('description');
            $table->decimal('cost', 15, 2)->default(0);
            $table->date('service_date');
            $table->date('next_service_date')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vehicle_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'service_date']);
            $table->index(['tenant_id', 'next_service_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('vehicles');
    }
};
