<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'stock_transfers_tenant_id_fk')->cascadeOnDelete();
            $table->string('reference_number');
            $table->foreignId('from_location_id')->constrained('warehouse_locations', 'id', 'stock_transfers_from_location_id_fk')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('warehouse_locations', 'id', 'stock_transfers_to_location_id_fk')->cascadeOnDelete();
            // $table->boolean('is_inter_org')->default(false); // flag for cross‑OrgUnit transfers
            // $table->foreignId('internal_sales_order_id')->nullable()->constrained('sales_orders', 'id', 'stock_transfers_internal_sales_order_id_fk'); // for inter‑OrgUnit trading
            // $table->foreignId('internal_purchase_order_id')->nullable()->constrained('purchase_orders', 'id', 'stock_transfers_internal_purchase_order_id_fk');
            $table->enum('status', ['draft', 'pending', 'in_transit', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('requested_by')->constrained('users', 'id', 'stock_transfers_requested_by_fk');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id', 'stock_transfers_approved_by_fk')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_number'], 'stock_transfers_tenant_ref_uk');
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained(null, 'id', 'stock_transfer_lines_stock_transfer_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(null, 'id', 'stock_transfer_lines_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_transfer_lines_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained(null, 'id', 'stock_transfer_lines_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained(null, 'id', 'stock_transfer_lines_serial_id_fk')->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'stock_transfer_lines_uom_id_fk');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
