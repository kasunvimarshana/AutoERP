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
        Schema::create('service_bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('bay_number')->unique();
            $table->string('name');
            $table->integer('capacity')->default(1);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'closed'])->default('available');
            $table->unsignedBigInteger('current_appointment_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('bay_number');
            $table->index('status');
            $table->index('current_appointment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_bays');
    }
};
