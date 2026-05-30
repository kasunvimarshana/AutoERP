<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->unsignedBigInteger('document_definition_id')->nullable();
            $table->string('document_number');
            $table->string('status');
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('source_module', 120)->nullable();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_reference', 180)->nullable();
            $table->string('title')->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_total', 20, 4)->default(0);
            $table->decimal('tax_total', 20, 4)->default(0);
            $table->decimal('grand_total', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'document_number'], 'documents_tenant_number_unique');
            $table->index(['tenant_id', 'document_type_id', 'status'], 'documents_tenant_type_status_index');
            $table->index(['tenant_id', 'document_date'], 'documents_tenant_date_index');
            $table->index(['tenant_id', 'created_at'], 'documents_tenant_created_at_index');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'documents_source_reference_idx');
            $table->index(['tenant_id', 'document_definition_id', 'status'], 'documents_definition_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
