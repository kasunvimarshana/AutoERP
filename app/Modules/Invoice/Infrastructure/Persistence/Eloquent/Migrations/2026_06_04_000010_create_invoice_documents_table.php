<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->foreignId('document_id')->nullable()->constrained('documents', 'id')->nullOnDelete();
            $table->string('document_type', 120)->nullable();
            $table->unsignedInteger('version_no')->default(1);
            $table->string('status')->default('draft')->comment('draft, generated, sent, voided, archived');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as render template or integration hints');

            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_id', 'document_type', 'version_no'], 'invoice_documents_version_uk');
            $table->index(['tenant_id', 'invoice_id', 'status'], 'invoice_documents_invoice_status_idx');
            $table->index(['tenant_id', 'document_id'], 'invoice_documents_document_idx');
            $table->index(['tenant_id', 'document_type'], 'invoice_documents_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_documents');
    }
};
