<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->nullable()->constrained('commercial_documents')->nullOnDelete();
            $table->foreignId('subledger_document_id')->nullable()->constrained('subledger_documents')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->decimal('amount', 19, 4);
            $table->timestamp('allocated_at');
            $table->json('metadata')->nullable();
            $table->index(['tenant_id', 'payment_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
