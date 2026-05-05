<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'approval_requests_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('workflow_config_id')->constrained('approval_workflow_configs', 'id', 'approval_requests_workflow_config_id_fk')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedInteger('current_step_order')->default(1);
            $table->foreignId('requested_by_user_id')->constrained('users', 'id', 'approval_requests_requested_by_user_id_fk')->cascadeOnDelete();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users', 'id', 'approval_requests_resolved_by_user_id_fk')->nullOnDelete();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'approval_requests_entity_idx');
            $table->index(['tenant_id', 'status'], 'approval_requests_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
