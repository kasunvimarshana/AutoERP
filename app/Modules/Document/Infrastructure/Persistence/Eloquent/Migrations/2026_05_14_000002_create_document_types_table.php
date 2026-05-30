<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->string('name');
            $table->string('code', 100);
            $table->text('description')->nullable();
            $table->string('module_scope', 120)->default('shared');
            $table->string('default_status')->default('draft');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_source')->default(false);
            $table->boolean('supports_items')->default(true);
            $table->boolean('supports_attachments')->default(true);
            $table->boolean('supports_comments')->default(true);
            $table->boolean('supports_versions')->default(true);
            $table->boolean('supports_workflow')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'document_types_tenant_code_unique');
            $table->index(['tenant_id', 'module_scope', 'is_active'], 'document_types_tenant_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
