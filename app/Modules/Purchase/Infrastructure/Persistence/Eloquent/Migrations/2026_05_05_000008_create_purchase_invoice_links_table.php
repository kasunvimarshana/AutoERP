<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('source_type')->comment('purchase_order, grn, purchase_return, direct_purchase');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('invoice_id')->constrained('invoices', 'id')->cascadeOnDelete();
            $table->foreignId('invoice_line_id')
                ->nullable()
                ->constrained('invoice_lines')
                ->nullOnDelete();
            $table->decimal('linked_quantity', 20, 4)->nullable();
            $table->decimal('linked_amount', 20, 4)->default(0);
            $table->decimal('source_quantity', 20, 4)->nullable()->comment('Original source line quantity available for allocation');
            $table->decimal('source_amount', 20, 4)->default(0)->comment('Original source line gross amount');
            $table->decimal('allocated_line_discount_amount', 20, 4)->default(0);
            $table->decimal('allocated_header_discount_amount', 20, 4)->default(0);
            $table->decimal('allocated_line_tax_amount', 20, 4)->default(0);
            $table->decimal('allocated_header_tax_amount', 20, 4)->default(0);
            $table->decimal('allocated_charge_amount', 20, 4)->default(0);
            $table->decimal('allocated_debit_adjustment_amount', 20, 4)->default(0);
            $table->decimal('allocated_credit_adjustment_amount', 20, 4)->default(0);
            $table->decimal('allocation_ratio', 12, 8)->default(0);
            $table->string('status')->default('active')->comment('active, reversed');
            $table->timestamp('linked_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'source_type', 'source_id', 'source_line_id', 'invoice_id', 'invoice_line_id'],
                'purchase_invoice_links_source_invoice_uk'
            );
            $table->index(['tenant_id', 'source_type', 'source_id'], 'purchase_invoice_links_source_idx');
            $table->index(['tenant_id', 'invoice_id', 'status'], 'purchase_invoice_links_invoice_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_links');
    }
};
