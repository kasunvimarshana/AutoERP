<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('default_supplier_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_purchase_discount_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_purchase_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_freight_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_return_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_rounding_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_write_off_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->foreignId('default_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('default_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('default_price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->foreignId('default_tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();

            $table->foreignId('purchase_order_document_definition_id')->nullable()->constrained('document_definitions')->nullOnDelete();
            $table->foreignId('grn_document_definition_id')->nullable()->constrained('document_definitions')->nullOnDelete();
            $table->foreignId('purchase_invoice_document_definition_id')->nullable()->constrained('document_definitions')->nullOnDelete();
            $table->foreignId('purchase_return_document_definition_id')->nullable()->constrained('document_definitions')->nullOnDelete();

            $table->boolean('require_po_before_grn')->default(false);
            $table->boolean('require_grn_before_invoice')->default(false);
            $table->boolean('allow_direct_grn')->default(true);
            $table->boolean('allow_direct_purchase_document')->default(true);
            $table->boolean('allow_return_without_original')->default(true);
            $table->boolean('allow_negative_stock_on_return')->default(false);
            $table->boolean('allow_header_discount')->default(true);
            $table->boolean('allow_line_discount')->default(true);
            $table->string('tax_calculation_level')->default('line')->comment('line, header, both');
            $table->string('header_discount_allocation_method')->default('proportional')->comment('proportional, equal, value_based');
            $table->string('default_po_status')->default('draft');
            $table->string('default_grn_status')->default('draft');
            $table->string('default_document_status')->default('draft');
            $table->string('default_return_status')->default('draft');
            $table->string('numbering_sequence_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'organization_unit_id'], 'purchase_settings_tenant_org_uk');
            $table->index(['tenant_id', 'is_active'], 'purchase_settings_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_settings');
    }
};
