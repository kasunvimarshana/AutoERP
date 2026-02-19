<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->ulid('tenant_id');
            $table->ulid('organization_id')->nullable();
            $table->foreignId('workflow_instance_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->onDelete('cascade');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('delegated_to')->nullable();
            $table->string('status', 50);
            $table->integer('priority')->default(1);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->text('comments')->nullable();
            $table->json('decision_data')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->integer('escalation_level')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_id']);
            $table->index(['workflow_instance_id', 'status']);
            $table->index(['approver_id', 'status']);
            $table->index(['delegated_to', 'status']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
