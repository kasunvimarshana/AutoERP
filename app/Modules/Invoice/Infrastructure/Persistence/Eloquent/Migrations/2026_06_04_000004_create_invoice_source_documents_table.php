<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_source_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->string('source_module', 120);
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->string('source_reference', 180)->nullable();
            $table->json('source_context')->nullable()->comment('Additional source context supplied by owning module');
            $table->string('relation_type')->default('source')->comment('source, adjustment_source, reversal_source, supporting_source');
            $table->decimal('amount_contributed', 20, 4)->default(0);
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'invoice_id', 'source_module', 'source_type', 'source_id'],
                'invoice_source_docs_unique'
            );
            $table->index(['tenant_id', 'invoice_id'], 'invoice_source_docs_invoice_idx');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'invoice_source_docs_source_idx');
            $table->index(['tenant_id', 'source_reference'], 'invoice_source_docs_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_source_documents');
    }
};
