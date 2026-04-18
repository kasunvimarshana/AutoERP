<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'grn_headers_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('supplier_id');
            $table->foreignId('warehouse_id');
            $table->foreignId('purchase_order_id')->nullable()->constrained(null, 'id', 'grn_headers_purchase_order_id_fk')->nullOnDelete(); // nullable for SMB direct buy
            $table->string('grn_number');
            $table->enum('status', ['draft', 'partial', 'complete', 'posted'])->default('draft');
            $table->date('received_date');
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'grn_headers_currency_id_fk');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by');
            $table->timestamps();

            $table->unique(['tenant_id', 'grn_number'], 'grn_headers_tenant_grn_uk');
        });

        Schema::create('grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_header_id')->constrained(null, 'id', 'grn_lines_grn_header_id_fk')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines', 'id', 'grn_lines_purchase_order_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('location_id');
            $table->foreignId('uom_id');
            $table->decimal('expected_qty', 15, 4)->default(0);
            $table->decimal('received_qty', 15, 4);
            $table->decimal('rejected_qty', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('line_cost', 15, 4)->storedAs('received_qty * unit_cost');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_lines');
        Schema::dropIfExists('grn_headers');
    }
};
