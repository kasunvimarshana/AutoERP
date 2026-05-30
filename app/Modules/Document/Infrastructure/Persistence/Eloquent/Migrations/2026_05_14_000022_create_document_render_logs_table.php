<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_render_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedBigInteger('document_template_id')->nullable();
            $table->string('render_type', 80)->default('preview');
            $table->string('status', 80)->default('rendered');
            $table->unsignedBigInteger('rendered_by')->nullable();
            $table->timestamp('rendered_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_id', 'created_at'], 'document_render_logs_document_idx');
            $table->index(['tenant_id', 'render_type', 'status'], 'document_render_logs_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_render_logs');
    }
};
