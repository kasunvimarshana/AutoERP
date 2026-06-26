<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Optimistic concurrency version.');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->restrictOnDelete();
            $table->unsignedBigInteger('organization_unit_id');

            $table->string('name');
            $table->char('active_name_hash', 64)->nullable()->comment('Case-insensitive uniqueness key for active documents.');
            $table->string('document_type')->nullable();
            $table->string('object_key');
            $table->string('original_filename');
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64);
            $table->string('scan_engine', 100);
            $table->dateTime('scanned_at');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['organization_unit_id', 'tenant_id'], 'organization_unit_documents_org_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->unique(['tenant_id', 'organization_unit_id', 'active_name_hash'], 'organization_unit_documents_active_name_uk');
            $table->unique(['tenant_id', 'object_key'], 'organization_unit_documents_object_key_uk');
            $table->unique(['id', 'tenant_id'], 'organization_unit_documents_id_tenant_uk');
            $table->index(['tenant_id', 'organization_unit_id', 'document_type'], 'organization_unit_documents_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_documents');
    }
};
