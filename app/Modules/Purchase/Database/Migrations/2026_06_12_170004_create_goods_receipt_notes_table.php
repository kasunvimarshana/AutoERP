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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('supplier_type')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
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
            $table->index(['tenant_id', 'organization_unit_id'], 'goods_receipt_notes_tenant_org_idx');
            $table->index(['supplier_type', 'supplier_id'], 'goods_receipt_notes_supplier_idx');
            $table->index('purchase_order_id', 'goods_receipt_notes_po_idx');
            $table->index('status', 'goods_receipt_notes_status_idx');
            $table->index('received_date', 'goods_receipt_notes_date_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'status'], 'goods_receipt_notes_scope_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
