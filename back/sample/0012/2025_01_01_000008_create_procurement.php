<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('vendor');
            // vendor|manufacturer|distributor|dropshipper|3pl
            $table->string('currency', 3)->default('USD');
            $table->integer('payment_terms_days')->default(30);
            $table->integer('lead_time_days')->nullable();
            $table->decimal('minimum_order_value', 20, 4)->nullable();
            $table->string('tax_id')->nullable();
            $table->json('address')->nullable();
            $table->json('contact')->nullable();
            $table->json('banking')->nullable();
            $table->string('status')->default('active');
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable()->index();
            $table->string('supplier_sku')->nullable();
            $table->string('supplier_product_name')->nullable();
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('lead_time_days')->nullable();
            $table->decimal('minimum_order_qty', 14, 4)->nullable();
            $table->decimal('order_multiple', 14, 4)->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('po_number')->unique();
            $table->string('status')->default('draft');
            // draft|pending_approval|approved|sent|partially_received|received|cancelled|closed
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 16, 6)->default(1);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('shipping_cost', 20, 4)->default(0);
            $table->decimal('other_charges', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('amount_received_value', 20, 4)->default(0);
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->integer('line_number');
            $table->string('description')->nullable();
            $table->decimal('quantity_ordered', 20, 4);
            $table->decimal('quantity_received', 20, 4)->default(0);
            $table->decimal('quantity_invoiced', 20, 4)->default(0);
            $table->decimal('quantity_cancelled', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('discount_percentage', 8, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4);
            $table->date('expected_date')->nullable();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('received_by')->index();
            $table->unsignedBigInteger('qc_approved_by')->nullable();
            $table->string('grn_number')->unique();
            $table->string('status')->default('draft');
            // draft|qc_pending|qc_approved|posted|cancelled
            $table->date('receipt_date');
            $table->string('supplier_delivery_note')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->decimal('total_received_value', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('purchase_order_line_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('lot_id')->nullable()->index();
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_expected', 20, 4)->nullable();
            $table->decimal('quantity_received', 20, 4);
            $table->decimal('quantity_accepted', 20, 4)->default(0);
            $table->decimal('quantity_rejected', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('line_total', 20, 4);
            $table->string('rejection_reason')->nullable();
            $table->string('condition')->default('new');
            // new|good|damaged|expired|near_expiry
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('goods_receipt_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->string('reference_number')->unique();
            $table->string('type');
            // freight|insurance|duty|customs|handling|inspection|brokerage|other
            $table->string('allocation_method');
            // by_value|by_quantity|by_weight|by_volume|equal
            $table->decimal('amount', 20, 4);
            $table->string('currency', 3)->default('USD');
            $table->string('vendor_name')->nullable();
            $table->string('invoice_number')->nullable();
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('landed_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landed_cost_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('goods_receipt_line_id')->index();
            $table->decimal('allocated_amount', 20, 4);
            $table->decimal('allocation_basis', 20, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_cost_allocations');
        Schema::dropIfExists('landed_costs');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('supplier_products');
        Schema::dropIfExists('suppliers');
    }
};
