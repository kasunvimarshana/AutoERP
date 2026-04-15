<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('period_id');
            $table->string('return_number', 50)->unique();
            $table->enum('direction', ['from_customer', 'to_supplier']);
            $table->unsignedBigInteger('party_id');
            $table->enum('original_order_type', ['sales_order', 'purchase_order', 'customer_invoice', 'supplier_invoice'])->nullable();
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('return_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['draft', 'approved', 'received', 'inspected', 'completed', 'cancelled'])->default('draft');
            $table->enum('restock_action', ['restock', 'quarantine', 'dispose', 'return_to_vendor'])->default('restock');
            $table->decimal('restocking_fee', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('period_id')->references('id')->on('accounting_periods')->cascadeOnDelete();
            $table->foreign('party_id')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->nullOnDelete();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['tenant_id', 'party_id', 'status']);
            $table->index(['original_order_type', 'original_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_orders');
    }
};