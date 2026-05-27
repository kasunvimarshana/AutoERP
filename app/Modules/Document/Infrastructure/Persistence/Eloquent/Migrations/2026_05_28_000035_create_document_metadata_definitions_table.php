<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_metadata_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->string('metadata_key', 120);
            $table->string('label', 180);
            $table->string('data_type', 40);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(1);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'entity_type', 'metadata_key'],
                'document_metadata_definitions_unique'
            );
            $table->index(['tenant_id', 'entity_type', 'display_order'], 'document_metadata_definitions_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_metadata_definitions');
    }
};
