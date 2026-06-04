<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_line_id')->constrained('invoice_lines', 'id')->cascadeOnDelete();

            $table->string('source_module', 120);
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('source_reference', 180)->nullable();
            $table->json('source_context')->nullable()->comment('Additional source line context supplied by owning module');
            $table->decimal('quantity_billed', 20, 4)->default(0);
            $table->decimal('amount_billed', 20, 4)->default(0);
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'invoice_line_id', 'source_module', 'source_type', 'source_id', 'source_line_id'],
                'invoice_line_sources_unique'
            );
            $table->index(['tenant_id', 'invoice_line_id'], 'invoice_line_sources_line_idx');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'invoice_line_sources_source_idx');
            $table->index(['tenant_id', 'source_reference'], 'invoice_line_sources_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_sources');
    }
};
