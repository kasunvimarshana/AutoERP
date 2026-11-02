<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants', 'id', indexName: 'user_documents_tenant_fk')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('active_name_key')->nullable();
            $table->string('document_type', 50);
            $table->string('object_key');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum_sha256', 64);
            $table->string('scan_engine', 100);
            $table->dateTime('scanned_at');
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id'], 'user_documents_id_tenant_uk');
            $table->unique(['tenant_id', 'object_key'], 'user_documents_object_key_uk');
            $table->unique(['tenant_id', 'user_id', 'active_name_key'], 'user_documents_active_name_uk');
            $table->foreign(['user_id', 'tenant_id'], 'user_documents_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['uploaded_by_user_id', 'tenant_id'], 'user_documents_uploaded_by_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['updated_by_user_id', 'tenant_id'], 'user_documents_updated_by_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};
