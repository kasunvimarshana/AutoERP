<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_job_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('service_job_card_id')->constrained('service_job_cards', 'id', 'sjp_job_card_id_fk')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->enum('line_type', ['inventory_item', 'non_inventory_item'])->default('inventory_item');

            // Inventory item fields
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'sjp_product_id_fk')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses', 'id', 'sjp_warehouse_id_fk')->nullOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations', 'id', 'sjp_location_id_fk')->nullOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements', 'id', 'sjp_stock_movement_id_fk')->nullOnDelete()
                ->comment('Linked when inventory is issued to the job');

            // Non-inventory / common fields
            $table->string('description')->nullable()->comment('Used for non-inventory items or custom description');
            $table->decimal('quantity', 20, 6)->default('1.000000');
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure', 'id', 'sjp_uom_id_fk')->nullOnDelete();
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('line_amount', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'allocated', 'issued', 'returned', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'service_job_card_id', 'sequence'], 'sjp_tenant_org_job_seq_uk');
            $table->index(['tenant_id', 'service_job_card_id', 'status'], 'sjp_tenant_job_status_idx');
            $table->index(['tenant_id', 'product_id', 'status'], 'sjp_tenant_product_status_idx');
        });

        Schema::create('service_warranty_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('service_job_card_id')->constrained('service_job_cards', 'id', 'swc_job_card_id_fk')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets', 'id', 'swc_asset_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers', 'id', 'swc_supplier_id_fk')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id', 'swc_customer_id_fk')->nullOnDelete();
            $table->string('warranty_reference')->nullable()->comment('Supplier warranty/claim reference number');
            $table->enum('claim_type', ['parts', 'labour', 'combined'])->default('combined');
            $table->decimal('claim_amount', 20, 6)->default('0.000000');
            $table->decimal('approved_amount', 20, 6)->default('0.000000');
            $table->decimal('settled_amount', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'settled', 'closed'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'swc_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'service_job_card_id', 'status'], 'swc_tenant_job_status_idx');
            $table->index(['tenant_id', 'supplier_id', 'status'], 'swc_tenant_supplier_status_idx');
        });

        Schema::create('service_procurement_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('service_job_card_id')->constrained('service_job_cards', 'id', 'spr_job_card_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'spr_product_id_fk')->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('quantity', 20, 6)->default('1.000000');
            $table->foreignId('uom_id')->nullable()->constrained('units_of_measure', 'id', 'spr_uom_id_fk')->nullOnDelete();
            $table->date('needed_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'ordered', 'received', 'cancelled'])->default('pending');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders', 'id', 'spr_po_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'service_job_card_id', 'status'], 'spr_tenant_job_status_idx');
            $table->index(['tenant_id', 'status', 'needed_by'], 'spr_tenant_status_needed_idx');
        });

        Schema::create('service_returns_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('service_job_card_id')->constrained('service_job_cards', 'id', 'srr_job_card_id_fk')->cascadeOnDelete();
            $table->enum('return_type', ['parts_return', 'customer_refund', 'supplier_credit']);
            $table->nullableMorphs('source');
            $table->decimal('amount', 20, 6)->default('0.000000');
            $table->enum('status', ['pending', 'processed', 'refunded', 'cancelled'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained('payments', 'id', 'srr_payment_id_fk')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries', 'id', 'srr_journal_entry_id_fk')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'service_job_card_id', 'return_type', 'status'], 'srr_tenant_job_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_returns_refunds');
        Schema::dropIfExists('service_procurement_requests');
        Schema::dropIfExists('service_warranty_claims');
        Schema::dropIfExists('service_job_parts');
    }
};
