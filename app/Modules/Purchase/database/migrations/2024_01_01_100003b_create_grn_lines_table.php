<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('grn_header_id')->constrained(null, 'id', 'grn_lines_grn_header_id_fk')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines', 'id', 'grn_lines_purchase_order_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('location_id');
            $table->foreignId('uom_id');
            $table->decimal('expected_qty', 20, 6)->default(0);
            $table->decimal('received_qty', 20, 6);
            $table->decimal('rejected_qty', 20, 6)->default(0);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('line_cost', 20, 6)->storedAs('received_qty * unit_cost');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_lines');
    }
};
