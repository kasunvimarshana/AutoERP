<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('uuid', 36)->unique('tenant_attachments_uuid_uq');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('type')->nullable()->index('idx_tenant_attachments_type');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common query patterns
            $table->index(['tenant_id', 'type'], 'idx_tenant_attachments_tenant_type');
            $table->index(['tenant_id', 'created_at'], 'idx_tenant_attachments_tenant_date');
            $table->index(['uuid'], 'idx_tenant_attachments_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_attachments');
    }
};
