<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_item_definition_field_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('field_definition_id')
                ->constrained('document_item_definition_fields')
                ->cascadeOnDelete();
            $table->string('option_key', 120);
            $table->string('option_label', 180);
            $table->string('option_value', 255)->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'field_definition_id', 'option_key'],
                'document_item_definition_field_options_unique'
            );
            $table->index(
                ['tenant_id', 'field_definition_id', 'display_order'],
                'document_item_definition_field_options_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_item_definition_field_options');
    }
};
