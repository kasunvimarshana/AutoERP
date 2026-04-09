<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit & Compliance Module
     *
     * audit_logs: polymorphic, immutable record of every create/update/delete
     *   across all entities. Stores old and new values as JSON snapshots.
     *   Designed for compliance (SOX, GAAP, IFRS, DSCSA, GxP).
     *
     * attachments: polymorphic file attachments (multipart/form-data).
     *   Can attach to any entity: invoice, GRN, product, return, etc.
     */
    public function up(): void
    {
        // ── Audit Logs ────────────────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();   // null = system action
            $table->string('event', 100);                        // created, updated, deleted, posted, etc.
            $table->string('auditable_type', 100);               // polymorphic model class
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();              // snapshot before change
            $table->json('new_values')->nullable();              // snapshot after change
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('method', 10)->nullable();            // GET, POST, PUT, DELETE
            $table->json('tags')->nullable();                    // categorization tags
            $table->timestamp('created_at')->useCurrent();       // immutable — no updated_at

            // No updated_at — audit records are immutable
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['tenant_id', 'event', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'user_id']);
        });

        // ── Attachments (polymorphic file attachments) ─────────────────────────
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('attachable_type', 100);   // polymorphic model
            $table->unsignedBigInteger('attachable_id');
            $table->string('file_name');
            $table->string('file_path', 500);         // storage path / S3 key
            $table->string('disk', 50)->default('s3'); // filesystem disk
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');           // bytes
            $table->string('label', 100)->nullable();  // e.g. "Certificate of Analysis", "Invoice Scan"
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');

            $table->index(['attachable_type', 'attachable_id']);
            $table->index(['tenant_id', 'mime_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('audit_logs');
    }
};
