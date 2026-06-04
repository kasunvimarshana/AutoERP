<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();

            $table->unsignedInteger('line_no');
            $table->string('line_type')->default('item')->comment('item, service, charge, discount, rounding, note');
            $table->foreignId('item_id')->nullable()->constrained('items', 'id')->nullOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->text('description');
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures', 'id')->nullOnDelete();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('line_subtotal', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);

            $table->string('source_module', 120)->nullable();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('source_reference', 180)->nullable();
            $table->json('source_context')->nullable()->comment('Additional source line context supplied by owning module');
            $table->unsignedInteger('schema_version')->default(1);
            $table->json('data_json')->nullable()->comment('Dynamic line payload for module-specific calculation extras');
            $table->json('metadata_json')->nullable()->comment('Non-domain metadata such as import keys or integration hints');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_id', 'line_no'], 'invoice_lines_invoice_line_no_uk');
            $table->index(['tenant_id', 'invoice_id'], 'invoice_lines_invoice_idx');
            $table->index(['tenant_id', 'line_type'], 'invoice_lines_type_idx');
            $table->index(['tenant_id', 'item_id', 'item_variant_id'], 'invoice_lines_item_variant_idx');
            $table->index(['tenant_id', 'source_module', 'source_type', 'source_id'], 'invoice_lines_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
