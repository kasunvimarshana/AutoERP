<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('template_code', 120);
            $table->string('template_name');
            $table->string('layout_type', 80)->default('html');
            $table->text('header_content')->nullable();
            $table->text('body_content')->nullable();
            $table->text('footer_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'template_code'], 'document_templates_tenant_code_unique');
            $table->index(['tenant_id', 'document_type_id', 'is_active'], 'document_templates_tenant_type_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
