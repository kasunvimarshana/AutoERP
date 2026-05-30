<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->foreignId('document_template_section_id')->nullable()->constrained('document_template_sections')->nullOnDelete();
            $table->string('field_key', 120);
            $table->string('label', 180)->nullable();
            $table->string('placeholder', 180);
            $table->string('field_role', 80)->default('metadata');
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_template_id', 'field_key'], 'document_template_fields_unique');
            $table->index(['tenant_id', 'document_template_id', 'display_order'], 'document_template_fields_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_fields');
    }
};
