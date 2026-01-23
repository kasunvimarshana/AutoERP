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
        Schema::create('job_card_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('job_card_id')->constrained()->cascadeOnDelete();
            $table->string('task_name');
            $table->text('task_description')->nullable();
            $table->enum('task_type', ['service', 'repair', 'inspection', 'diagnostic', 'custom'])->default('service');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('sequence_order')->default(0);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('estimated_minutes')->nullable();
            $table->integer('actual_minutes')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['job_card_id', 'status']);
            $table->index('sequence_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_card_tasks');
    }
};
