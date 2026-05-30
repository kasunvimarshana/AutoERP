<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->string('definition_code', 120)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('source_module', 120)->default('shared');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('sequence_id')->nullable();
            $table->unsignedBigInteger('workflow_id')->nullable();
            $table->string('default_status', 120)->default('draft');
            $table->boolean('supports_versions')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type_id', 'version'], 'document_definitions_unique');
            $table->index(['tenant_id', 'is_active'], 'document_definitions_tenant_active_index');
            $table->index(['tenant_id', 'source_module', 'is_active'], 'document_definitions_source_active_idx');
            $table->index(['tenant_id', 'definition_code'], 'document_definitions_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definitions');
    }
};
