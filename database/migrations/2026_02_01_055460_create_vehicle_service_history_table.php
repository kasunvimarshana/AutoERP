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
        Schema::create('vehicle_service_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->enum('service_type', ['routine', 'repair', 'inspection']);
            $table->integer('odometer_reading');
            $table->text('description')->nullable();
            $table->text('parts_used')->nullable();
            $table->decimal('labor_cost', 15, 2)->default(0);
            $table->decimal('parts_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['vehicle_id', 'service_date']);
            $table->index(['branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_history');
    }
};
