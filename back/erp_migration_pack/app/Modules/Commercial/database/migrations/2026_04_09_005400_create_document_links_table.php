<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('from_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->foreignId('to_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->string('link_type');
            $table->foreignId('from_line_id')->nullable()->constrained('commercial_document_lines')->nullOnDelete();
            $table->foreignId('to_line_id')->nullable()->constrained('commercial_document_lines')->nullOnDelete();
            $table->decimal('quantity', 24, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->unique(['from_document_id', 'to_document_id', 'link_type']);
            $table->index(['tenant_id', 'link_type']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_links');
    }
};
