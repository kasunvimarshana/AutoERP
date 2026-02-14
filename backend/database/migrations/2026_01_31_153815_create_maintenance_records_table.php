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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('fleet_vehicle_id')->constrained('fleet_vehicles')->onDelete('cascade');
            $table->string('maintenance_type');
            $table->text('description')->nullable();
            $table->date('service_date');
            $table->integer('mileage_at_service');
            $table->decimal('cost', 15, 2)->default(0);
            $table->string('performed_by')->nullable();
            $table->date('next_service_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('fleet_vehicle_id');
            $table->index('service_date');
            $table->index('status');
            $table->index('next_service_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
