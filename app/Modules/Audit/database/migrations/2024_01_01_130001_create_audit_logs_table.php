<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable()->index('audit_logs_tenant_idx');
            $table->unsignedBigInteger('org_unit_id')->nullable()->index('audit_logs_org_unit_idx');
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->unsignedBigInteger('user_id')->nullable()->index('audit_logs_user_idx');

            // The action that triggered this entry (created, updated, deleted, etc.)
            $table->string('event', 50)->index('audit_logs_event_idx');

            // Polymorphic morph columns
            // $table->morphs('auditable');
            $table->string('auditable_type')->index('audit_logs_auditable_type_idx');
            $table->string('auditable_id')->index('audit_logs_auditable_id_idx');

            // Captured attribute snapshots
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context
            $table->string('url', 1000)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Extensibility
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            // Audit logs are only ever created, never updated.
            // $table->timestamp('created_at')->useCurrent();
            $table->timestamp('occurred_at')->useCurrent();
            $table->softDeletes();

            $table->index(['tenant_id', 'auditable_type', 'auditable_id'], 'audit_logs_tenant_morphable_idx');
            $table->index(['tenant_id', 'occurred_at'], 'audit_logs_tenant_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
