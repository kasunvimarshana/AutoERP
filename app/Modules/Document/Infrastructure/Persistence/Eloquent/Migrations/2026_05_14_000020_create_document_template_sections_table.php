<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->string('section_key', 120);
            $table->string('section_label', 180);
            $table->string('section_type', 80)->default('content');
            $table->unsignedInteger('display_order')->default(1);
            $table->text('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_template_id', 'section_key'], 'document_template_sections_unique');
            $table->index(['tenant_id', 'document_template_id', 'display_order'], 'document_template_sections_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_template_sections');
    }
};
