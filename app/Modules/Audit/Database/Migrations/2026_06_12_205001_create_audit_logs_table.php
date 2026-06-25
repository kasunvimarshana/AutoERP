<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique('audit_logs_event_uuid_uk');
            $table->string('producer_key', 190)->nullable();
            $table->char('producer_fingerprint', 64)->nullable()->unique('audit_logs_producer_fingerprint_uk');

            // Deliberately no foreign keys: audit history must survive owner/user lifecycle changes.
            // Scope combinations are validated before append and human-readable snapshots are retained.
            $table->unsignedBigInteger('tenant_id');
            $table->string('tenant_name');
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->string('organization_unit_name')->nullable();

            $table->string('event_category', 40);
            $table->string('event_name', 150);

            $table->string('actor_type', 32);
            $table->string('actor_id', 100);
            $table->string('actor_name');
            $table->string('actor_guard', 64)->nullable();
            $table->string('actor_provider', 100)->nullable();
            $table->string('application_id', 100)->nullable();
            $table->unsignedBigInteger('impersonator_user_id')->nullable();

            $table->string('subject_type', 100);
            $table->string('subject_id', 150);
            $table->string('subject_reference')->nullable();

            $table->string('source_module', 100);
            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 150)->nullable();
            $table->string('source_reference')->nullable();

            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();

            $table->string('request_id', 100)->nullable();
            $table->string('request_method', 12)->nullable();
            $table->string('route_name')->nullable();
            $table->string('route_path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(
                ['tenant_id', 'organization_unit_id', 'occurred_at', 'id'],
                'audit_logs_scope_time_idx',
            );
            $table->index(
                ['tenant_id', 'event_name', 'occurred_at', 'id'],
                'audit_logs_event_time_idx',
            );
            $table->index(
                ['tenant_id', 'actor_type', 'actor_id', 'occurred_at', 'id'],
                'audit_logs_actor_time_idx',
            );
            $table->index(
                ['tenant_id', 'subject_type', 'subject_id', 'occurred_at', 'id'],
                'audit_logs_subject_time_idx',
            );
            $table->index(
                ['tenant_id', 'source_module', 'source_type', 'source_id', 'occurred_at'],
                'audit_logs_source_time_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
