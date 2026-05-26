<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_documents_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_documents_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('tenant_documents_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name'], 'tenant_documents_tenant_name_uk');
            $table->index(['tenant_id', 'type', 'is_public'], 'tenant_documents_tenant_type_public_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
