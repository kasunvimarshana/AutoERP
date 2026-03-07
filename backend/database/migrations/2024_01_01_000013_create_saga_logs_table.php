<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saga log table persists the execution state of each saga run.
 * Enables observability, manual recovery, and duplicate-run prevention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_logs', function (Blueprint $table) {
            $table->id();
            $table->string('saga_id')->unique();       // Correlates all steps of one run
            $table->string('saga_type')->default('generic'); // e.g. 'place_order'
            $table->enum('status', [
                'started',
                'completed',
                'failed',
                'compensating',
                'compensated',
            ])->default('started');
            $table->string('current_step')->nullable(); // Name of the step currently running
            $table->json('context')->nullable();        // Snapshot of the shared context
            $table->json('compensation_log')->nullable(); // Results of compensation steps
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('saga_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_logs');
    }
};
