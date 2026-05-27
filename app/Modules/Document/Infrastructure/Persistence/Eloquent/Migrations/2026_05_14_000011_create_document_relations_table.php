<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('target_document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('relation_type')->default('reference');
            $table->timestamps();

            $table->unique(
                ['source_document_id', 'target_document_id', 'relation_type'],
                'document_relations_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_relations');
    }
};
