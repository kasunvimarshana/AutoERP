<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'purchase_returns_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id');
            $table->foreignId('original_grn_id')->nullable()->constrained('grn_headers', 'id', 'purchase_returns_original_grn_id_fk')->nullOnDelete();
            $table->foreignId('original_invoice_id')->nullable()->constrained('purchase_invoices', 'id', 'purchase_returns_original_invoice_id_fk')->nullOnDelete();
            $table->string('return_number');
            $table->enum('status', ['draft', 'approved', 'shipped', 'closed', 'cancelled'])->default('draft');
            $table->date('return_date');
            $table->string('return_reason')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'purchase_returns_currency_id_fk');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_total', 15, 4)->default(0);
            $table->decimal('grand_total', 15, 4)->default(0);
            // $table->decimal('grand_total', 15, 4)->storedAs('subtotal + tax_total');
            $table->string('debit_note_number')->nullable();
            // Purchase returns JE
            $table->foreignId('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'return_number'], 'purchase_returns_tenant_return_uk');
        });

        Schema::create('purchase_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained(null, 'id', 'purchase_return_lines_purchase_return_id_fk')->cascadeOnDelete();
            $table->foreignId('original_grn_line_id')->nullable()->constrained('grn_lines', 'id', 'purchase_return_lines_original_grn_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('from_location_id');
            $table->foreignId('uom_id');
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
