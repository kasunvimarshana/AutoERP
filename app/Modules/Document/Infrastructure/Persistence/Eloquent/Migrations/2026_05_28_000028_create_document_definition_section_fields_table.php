<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_definition_section_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('document_definition_sections')->cascadeOnDelete();
            $table->foreignId('field_definition_id')
                ->constrained('document_definition_fields')
                ->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'section_id', 'field_definition_id'],
                'document_definition_section_fields_unique'
            );
            $table->index(
                ['tenant_id', 'section_id', 'display_order'],
                'document_definition_section_fields_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definition_section_fields');
    }
};
