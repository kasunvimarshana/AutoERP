<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saga audit log table.
 *
 * Records every step state transition within a Saga transaction.
 * Essential for distributed transaction observability and crash recovery.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('saga_id')->comment('Correlation ID for the Saga transaction');
            $table->string('order_id')->nullable();
            $table->string('step_name');
            $table->string('status')
                ->comment('completed|failed|compensated|compensation_failed');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at – saga logs are append-only

            $table->index('saga_id');
            $table->index('order_id');
            $table->index(['saga_id', 'step_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_logs');
    }
};
