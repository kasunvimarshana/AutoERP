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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('return_number', 50);
            $table->enum('direction', ['from_customer', 'to_supplier']);
            $table->foreignId('party_id')->constrained('parties')->restrictOnDelete();
            $table->enum('original_order_type', ['sales_order', 'purchase_order', 'customer_invoice', 'supplier_invoice'])->nullable();
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('return_date');
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['draft', 'approved', 'received', 'inspected', 'completed', 'cancelled'])->default('draft');
            $table->enum('restock_action', ['restock', 'quarantine', 'dispose', 'return_to_vendor']);
            $table->decimal('restocking_fee', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->foreignId('credit_note_id')->nullable()->constrained('credit_notes')->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'return_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_orders');
    }
};
