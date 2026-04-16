<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_grn_id')->nullable()->constrained('grn_headers')->nullOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();
            $table->string('return_number');
            $table->enum('status', ['draft', 'approved', 'shipped', 'closed', 'cancelled'])->default('draft');
            $table->date('return_date');
            $table->string('return_reason')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            $table->string('debit_note_number')->nullable();
            // Purchase returns JE
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'return_number'], 'uq_purchase_returns_tenant_return');
        });

        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_grn_line_id')->nullable()->constrained('grn_lines')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure');
            $table->decimal('return_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('line_cost', 15, 4)->storedAs('return_qty * unit_cost');
            $table->enum('condition', ['good', 'damaged', 'expired', 'defective'])->default('good');
            $table->enum('disposition', ['restock', 'scrap', 'return_to_vendor'])->default('return_to_vendor');
            $table->decimal('restocking_fee', 15, 4)->default(0);
            $table->text('quality_check_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
    }
};