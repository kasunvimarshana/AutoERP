<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'goods_receipt_notes_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable();
            $table->string('supplier_type')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('warehouse_id');
            $table->foreignId('warehouse_location_id')->nullable();
            $table->string('grn_number');
            $table->date('received_date');
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 20, 6)->default('0.000000');
            $table->decimal('discount_total', 20, 6)->default('0.000000');
            $table->decimal('tax_total', 20, 6)->default('0.000000');
            $table->decimal('charge_total', 20, 6)->default('0.000000');
            $table->decimal('grand_total', 20, 6)->default('0.000000');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'grn_number'], 'goods_receipt_notes_tenant_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'goods_receipt_notes_tenant_org_ix');
            $table->index(['supplier_type', 'supplier_id'], 'goods_receipt_notes_supplier_ix');
            $table->index('purchase_order_id', 'goods_receipt_notes_po_ix');
            $table->index('status', 'goods_receipt_notes_status_ix');
            $table->index('received_date', 'goods_receipt_notes_date_ix');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'goods_receipt_notes_scope_status_ix');

            $table->unique(['id', 'tenant_id'], 'goods_receipt_notes_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'goods_receipt_notes_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['purchase_order_id', 'tenant_id'], 'goods_receipt_notes_purchase_order_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('purchase_orders')
                ->restrictOnDelete();
            $table->foreign(['warehouse_id', 'tenant_id'], 'goods_receipt_notes_warehouse_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouses')
                ->restrictOnDelete();
            $table->foreign(['warehouse_location_id', 'tenant_id'], 'goods_receipt_notes_warehouse_location_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('warehouse_locations')
                ->restrictOnDelete();

            $table->foreign(['received_by', 'tenant_id'], 'goods_receipt_notes_received_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['posted_by', 'tenant_id'], 'goods_receipt_notes_posted_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
            $table->foreign(['reversed_by', 'tenant_id'], 'goods_receipt_notes_reversed_by_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
