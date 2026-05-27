<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_definition_item_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_definition_id')->constrained('document_definitions')->cascadeOnDelete();
            $table->foreignId('item_type_id')->constrained('document_item_types')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(1);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'document_definition_id', 'item_type_id'],
                'document_definition_item_types_unique'
            );
            $table->index(
                ['tenant_id', 'document_definition_id', 'display_order'],
                'document_definition_item_types_order_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definition_item_types');
    }
};
