<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_definition_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_definition_id')->constrained('document_definitions')->cascadeOnDelete();
            $table->string('field_key', 120);
            $table->string('label', 180);
            $table->string('data_type', 40);
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('display_order')->default(1);
            $table->string('default_value')->nullable();
            $table->text('validation_rule')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'document_definition_id', 'field_key'],
                'document_definition_fields_unique'
            );
            $table->index(['tenant_id', 'document_definition_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definition_fields');
    }
};
