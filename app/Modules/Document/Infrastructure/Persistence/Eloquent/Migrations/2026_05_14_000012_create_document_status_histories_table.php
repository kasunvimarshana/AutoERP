<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('action_name')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'document_id'], 'document_status_histories_tenant_document_index');
            $table->index(['document_id', 'created_at'], 'document_status_histories_document_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_status_histories');
    }
};
