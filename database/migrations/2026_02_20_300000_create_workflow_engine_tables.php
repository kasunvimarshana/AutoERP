<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflow definitions — one per entity type per tenant
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->string('entity_type'); // e.g. "order", "invoice", "purchase"
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'entity_type', 'name']);
        });

        // States belonging to a workflow definition
        Schema::create('workflow_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('workflow_definition_id')->index();
            $table->string('name');
            $table->string('label')->nullable();
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workflow_definition_id')
                ->references('id')->on('workflow_definitions')
                ->cascadeOnDelete();

            $table->unique(['workflow_definition_id', 'name']);
        });

        // Allowed transitions between states
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('workflow_definition_id')->index();
            $table->uuid('from_state_id')->index();
            $table->uuid('to_state_id')->index();
            $table->string('name');
            $table->string('required_permission')->nullable();
            $table->json('conditions')->nullable(); // JSON-encoded condition rules
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workflow_definition_id')
                ->references('id')->on('workflow_definitions')
                ->cascadeOnDelete();

            $table->foreign('from_state_id')
                ->references('id')->on('workflow_states')
                ->cascadeOnDelete();

            $table->foreign('to_state_id')
                ->references('id')->on('workflow_states')
                ->cascadeOnDelete();

            $table->unique(['workflow_definition_id', 'from_state_id', 'to_state_id']);
        });

        // Running instances — one per entity
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('workflow_definition_id')->index();
            $table->uuid('current_state_id')->index();
            $table->string('entity_type');
            $table->uuid('entity_id')->index();
            $table->string('status')->default('active'); // active | completed | cancelled
            $table->uuid('initiated_by')->nullable(); // user id
            $table->json('context')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workflow_definition_id')
                ->references('id')->on('workflow_definitions')
                ->cascadeOnDelete();

            $table->foreign('current_state_id')
                ->references('id')->on('workflow_states')
                ->cascadeOnDelete();

            $table->unique(['entity_type', 'entity_id', 'workflow_definition_id']);
        });

        // Immutable history of every state transition
        Schema::create('workflow_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('workflow_instance_id')->index();
            $table->uuid('transition_id')->nullable()->index();
            $table->uuid('from_state_id')->nullable();
            $table->uuid('to_state_id');
            $table->uuid('transitioned_by')->nullable(); // user id
            $table->text('comment')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('transitioned_at');
            $table->timestamps();

            $table->foreign('workflow_instance_id')
                ->references('id')->on('workflow_instances')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_histories');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflow_states');
        Schema::dropIfExists('workflow_definitions');
    }
};
