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
            $table->foreignId('period_id')->constrained('accounting_periods')->cascadeOnDelete();
            $table->string('return_number', 50)->unique();
            $table->enum('direction', ['from_customer', 'to_supplier']);
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->enum('original_order_type', ['sales_order', 'purchase_order', 'customer_invoice', 'supplier_invoice'])->nullable();
            $table->unsignedBigInteger('original_order_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('return_date');
            $table->string('reason')->nullable();
            $table->enum('status', ['draft', 'approved', 'received', 'inspected', 'completed', 'cancelled'])->default('draft');
            $table->enum('restock_action', ['restock', 'quarantine', 'dispose', 'return_to_vendor'])->default('restock');
            $table->decimal('restocking_fee', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->unsignedBigInteger('credit_note_id')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_orders');
    }
};