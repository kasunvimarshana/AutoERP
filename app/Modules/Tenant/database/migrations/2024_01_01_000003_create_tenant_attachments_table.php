<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id', 'tenant_attachments_tenant_id_fk')->cascadeOnDelete();
            $table->string('uuid', 36)->unique('tenant_attachments_uuid_uk');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type', 127);
            $table->unsignedBigInteger('size')->default(0);
            $table->string('type')->nullable()->index('tenant_attachments_type_idx');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common query patterns
            $table->index(['tenant_id', 'type'], 'tenant_attachments_tenant_type_idx');
            $table->index(['tenant_id', 'created_at'], 'tenant_attachments_tenant_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_attachments');
    }
};
