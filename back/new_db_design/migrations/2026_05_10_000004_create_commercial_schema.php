<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type_code', 50);
            $table->string('type_name', 150);
            $table->string('category_code', 50);
            $table->boolean('affects_inventory')->default(false);
            $table->boolean('affects_subledger')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'type_code']);
        });

        Schema::create('commercial_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('bill_to_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('ship_to_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->string('document_number', 100);
            $table->string('external_reference', 150)->nullable();
            $table->string('status_code', 30)->default('draft');
            $table->string('fulfillment_status_code', 30)->default('pending');
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->decimal('subtotal_amount', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('grand_total_amount', 20, 6)->default(0);
            $table->decimal('cost_total_amount', 20, 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'document_number']);
            $table->index(['tenant_id', 'document_type_id', 'status_code', 'document_date'], 'commercial_documents_type_status_date_idx');
            $table->index(['tenant_id', 'party_id', 'document_date']);
            $table->index(['tenant_id', 'warehouse_id', 'document_date']);
            $table->index(['tenant_id', 'due_date', 'status_code']);
        });

        Schema::create('commercial_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->unsignedBigInteger('source_document_line_id')->nullable();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('unit_of_measure_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->unsignedInteger('line_no');
            $table->text('description')->nullable();
            $table->decimal('ordered_quantity', 24, 8)->default(0);
            $table->decimal('fulfilled_quantity', 24, 8)->default(0);
            $table->decimal('invoiced_quantity', 24, 8)->default(0);
            $table->decimal('unit_price', 20, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0);
            $table->decimal('net_amount', 20, 6)->default(0);
            $table->decimal('tax_amount', 20, 6)->default(0);
            $table->decimal('gross_amount', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'commercial_document_id', 'line_no'], 'commercial_document_lines_line_uk');
            $table->index(['tenant_id', 'product_variant_id']);
            $table->index(['tenant_id', 'warehouse_id', 'location_id']);
            $table->index(['tenant_id', 'source_document_line_id']);
        });

        Schema::table('commercial_document_lines', function (Blueprint $table) {
            $table->foreign('source_document_line_id')->references('id')->on('commercial_document_lines')->nullOnDelete();
        });

        Schema::create('commercial_document_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('commercial_document_line_id')->constrained('commercial_document_lines')->cascadeOnDelete();
            $table->string('tax_code', 50);
            $table->decimal('tax_rate', 10, 6);
            $table->decimal('taxable_amount', 20, 6);
            $table->decimal('tax_amount', 20, 6);
            $table->timestamps();

            $table->index(['tenant_id', 'commercial_document_line_id']);
        });

        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('source_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->foreignId('target_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->string('link_type', 50);
            $table->timestamps();

            $table->index(['tenant_id', 'source_document_id', 'link_type']);
            $table->index(['tenant_id', 'target_document_id', 'link_type']);
        });

        Schema::create('document_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->constrained('commercial_documents')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status_code', 30)->nullable();
            $table->string('to_status_code', 30);
            $table->text('reason_text')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'commercial_document_id', 'changed_at']);
            $table->index(['tenant_id', 'to_status_code', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_status_history');
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('commercial_document_taxes');
        Schema::table('commercial_document_lines', function (Blueprint $table) {
            $table->dropForeign(['source_document_line_id']);
        });
        Schema::dropIfExists('commercial_document_lines');
        Schema::dropIfExists('commercial_documents');
        Schema::dropIfExists('document_types');
    }
};
