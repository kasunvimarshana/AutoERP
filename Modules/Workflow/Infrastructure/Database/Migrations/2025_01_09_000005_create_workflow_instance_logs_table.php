<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_instance_id')
                ->constrained('workflow_instances')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('from_state_id')
                ->nullable()
                ->constrained('workflow_states')
                ->nullOnDelete();
            $table->foreignId('to_state_id')
                ->constrained('workflow_states');
            $table->foreignId('transition_id')
                ->nullable()
                ->constrained('workflow_transitions')
                ->nullOnDelete();
            $table->text('comment')->nullable();
            $table->unsignedBigInteger('actor_user_id');
            $table->timestamp('acted_at');
            $table->timestamp('created_at')->nullable();

            $table->index('workflow_instance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instance_logs');
    }
};
