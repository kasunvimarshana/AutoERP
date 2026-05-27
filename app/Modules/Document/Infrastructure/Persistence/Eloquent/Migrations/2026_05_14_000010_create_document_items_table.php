<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('item_type');
            $table->string('description')->nullable();
            $table->decimal('line_total', 20, 4)->default(0);
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'document_id'], 'document_items_tenant_document_index');
            $table->index(['document_id', 'sequence'], 'document_items_document_sequence_index');
            $table->index(['item_type'], 'document_items_type_index');
            $table->index(['tenant_id', 'created_at'], 'document_items_tenant_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
