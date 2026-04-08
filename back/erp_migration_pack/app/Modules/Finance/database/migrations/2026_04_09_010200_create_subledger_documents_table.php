<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subledger_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_accounts')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->nullable()->constrained('commercial_documents')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('subledger_type');
            $table->string('document_number');
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default("open");
            $table->decimal('original_amount', 19, 4);
            $table->decimal('outstanding_amount', 19, 4);
            $table->json('metadata')->nullable();
            $table->unique(['tenant_id', 'document_number']);
            $table->index(['tenant_id', 'subledger_type']);
            $table->index(['tenant_id', 'party_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subledger_documents');
    }
};
