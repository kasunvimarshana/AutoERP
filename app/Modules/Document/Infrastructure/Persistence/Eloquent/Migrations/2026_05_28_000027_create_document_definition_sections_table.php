<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_definition_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_definition_id')->constrained('document_definitions')->cascadeOnDelete();
            $table->string('section_key', 120);
            $table->string('label', 180);
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'document_definition_id', 'section_key'],
                'document_definition_sections_unique'
            );
            $table->index(
                ['tenant_id', 'document_definition_id', 'display_order'],
                'document_definition_sections_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definition_sections');
    }
};
