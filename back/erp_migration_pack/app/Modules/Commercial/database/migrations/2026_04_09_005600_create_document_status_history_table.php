<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->timestamp('changed_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->index(['tenant_id', 'commercial_document_id']);
            $table->index(['commercial_document_id', 'changed_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_status_history');
    }
};
