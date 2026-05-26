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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->unsignedBigInteger('tenant_id')->nullable()->index('audit_logs_tenant_idx')->comment('Multi-tenant owner reference');
            $table->unsignedBigInteger('organization_unit_id')->nullable()->index('audit_logs_organization_unit_idx')->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->unsignedBigInteger('user_id')->nullable()->index('audit_logs_user_idx');

            $table->string('event')->index('audit_logs_event_idx')->comment('The action that triggered this entry (created, updated, deleted, etc.)');

            // Polymorphic morph columns
            // $table->morphs('auditable');
            $table->string('auditable_type')->index('audit_logs_auditable_type_idx');
            $table->string('auditable_id')->index('audit_logs_auditable_id_idx');

            // Captured attribute snapshots
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context
            $table->string('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Extensibility
            $table->json('tags')->nullable();

            // Audit logs are only ever created, never updated.
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'organization_unit_id', 'auditable_type', 'auditable_id'], 'audit_logs_auditable_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'occurred_at'], 'audit_logs_occurred_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
