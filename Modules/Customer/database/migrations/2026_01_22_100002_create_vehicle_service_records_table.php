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
        Schema::create('vehicle_service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('service_number')->unique()->comment('Unique service record identifier');
            $table->string('branch_id')->nullable()->comment('Branch where service was performed');
            $table->date('service_date');
            $table->integer('mileage_at_service')->comment('Odometer reading at time of service');
            $table->enum('service_type', ['regular', 'major', 'repair', 'inspection', 'warranty', 'emergency'])->default('regular');
            $table->text('service_description')->nullable();
            $table->text('parts_used')->nullable()->comment('JSON array of parts used');
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->string('technician_name')->nullable();
            $table->foreignId('technician_id')->nullable()->comment('User ID of the technician');
            $table->text('notes')->nullable();
            $table->integer('next_service_mileage')->nullable();
            $table->date('next_service_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('completed');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('vehicle_id');
            $table->index('customer_id');
            $table->index('service_number');
            $table->index('service_date');
            $table->index('branch_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_records');
    }
};
