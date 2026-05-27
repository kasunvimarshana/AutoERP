<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('change_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_id'], 'document_versions_tenant_document_index');
            $table->index(['tenant_id', 'created_at'], 'document_versions_tenant_created_at_index');
            $table->unique(['tenant_id', 'document_id', 'version'], 'document_versions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
