<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_document_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('source_type')->comment('purchase_order, grn, purchase_return, direct_purchase');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('document_id')->constrained('documents', 'id')->cascadeOnDelete();
            $table->foreignId('document_line_id')
                ->nullable()
                ->constrained('document_items')
                ->nullOnDelete();
            $table->decimal('linked_quantity', 20, 4)->nullable();
            $table->decimal('linked_amount', 20, 4)->default(0);
            $table->string('status')->default('active')->comment('active, reversed');
            $table->timestamp('linked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'source_type', 'source_id', 'source_line_id', 'document_id', 'document_line_id'],
                'purchase_document_links_source_document_uk'
            );
            $table->index(['tenant_id', 'source_type', 'source_id'], 'purchase_document_links_source_idx');
            $table->index(['tenant_id', 'document_id', 'status'], 'purchase_document_links_document_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_document_links');
    }
};
