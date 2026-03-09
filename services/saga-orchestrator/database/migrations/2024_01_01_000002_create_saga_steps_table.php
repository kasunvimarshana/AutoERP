<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saga_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('saga_id')->constrained('saga_transactions')->cascadeOnDelete();
            $table->integer('step_order');
            $table->string('step_name', 100);
            $table->string('service', 100);
            $table->string('endpoint')->nullable();
            $table->string('compensation_endpoint')->nullable();
            $table->enum('status', ['PENDING', 'RUNNING', 'COMPLETED', 'COMPENSATING', 'COMPENSATED', 'FAILED', 'SKIPPED']);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('compensated_at')->nullable();
            $table->timestamps();

            $table->index(['saga_id', 'step_order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_steps');
    }
};
