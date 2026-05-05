<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('supplier_id');
            $table->foreignId('warehouse_id');
            $table->string('po_number');
            $table->enum('status', ['draft', 'sent', 'confirmed', 'partial', 'received', 'closed', 'cancelled'])->default('draft');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'purchase_orders_currency_id_fk');
            $table->decimal('exchange_rate', 20, 10)->default(1);
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('tax_total', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            // $table->decimal('grand_total', 20, 6)->storedAs('subtotal + tax_total - discount_total');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('approved_by')->nullable();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'po_number'], 'purchase_orders_tenant_po_number_uk');
            $table->index(['tenant_id', 'supplier_id', 'status'], 'purchase_orders_tenant_supplier_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
