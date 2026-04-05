<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // The action that triggered this entry (created, updated, deleted, etc.)
            $table->string('event', 50)->index();

            // Polymorphic morph columns
            $table->string('auditable_type')->index();
            $table->string('auditable_id')->index();

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
            $table->timestamp('created_at')->useCurrent();

            // Composite index for common queries: all logs for a specific record
            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
