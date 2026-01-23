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
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('bay_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('bay_type', ['general', 'specialized', 'diagnostic', 'quick_service'])->default('general');
            $table->enum('status', ['available', 'occupied', 'maintenance', 'inactive'])->default('available');
            $table->integer('capacity')->default(1);
            $table->json('equipment')->nullable(); // List of equipment available in this bay
            $table->json('specializations')->nullable(); // Types of services this bay can handle
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index('bay_number');
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
