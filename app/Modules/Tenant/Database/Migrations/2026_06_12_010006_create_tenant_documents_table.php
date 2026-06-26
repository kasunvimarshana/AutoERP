<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'tenant_documents_tenant_fk')->restrictOnDelete();
            $table->string('name');
            $table->string('document_type', 100)->nullable();
            $table->string('object_key');
            $table->string('original_filename');
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64);
            $table->string('scan_engine', 100);
            $table->dateTime('scanned_at');
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_documents_created_by_ix');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_documents_updated_by_ix');
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'tenant_documents_tenant_name_uk');
            $table->unique(['tenant_id', 'object_key'], 'tenant_documents_tenant_object_uk');
            $table->index(['tenant_id', 'document_type', 'name'], 'tenant_documents_tenant_type_name_ix');
            $table->unique(['id', 'tenant_id'], 'tenant_documents_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_documents');
    }
};
