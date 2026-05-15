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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('workflow_config_id')->constrained('approval_workflow_configs')->cascadeOnDelete();
            $table->string('entity_type')->comment('the model being approved (e.g., Document, JournalEntry)');
            $table->unsignedBigInteger('entity_id')->comment('the ID of the entity');
            $table->string('status')->default('pending')->comment('pending, approved, rejected, cancelled');
            $table->unsignedInteger('current_step_order')->default(1);
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id', 'entity_type', 'entity_id'], 'approval_requests_entity_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'approval_requests_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
