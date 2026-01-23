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
        Schema::create('digital_inspections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('job_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->string('inspection_type')->default('pre_service'); // pre_service, post_service, detailed
            $table->json('inspection_data')->nullable(); // Structured inspection checklist results
            $table->json('photos')->nullable(); // Array of photo URLs
            $table->text('overall_notes')->nullable();
            $table->enum('overall_status', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->dateTime('inspected_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('job_card_id');
            $table->index('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_inspections');
    }
};
