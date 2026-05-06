<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('product_id')->constrained('products', 'id', 'stock_movements_product_id_fk')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants', 'id', 'stock_movements_variant_id_fk')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id', 'stock_movements_batch_id_fk')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id', 'stock_movements_serial_id_fk')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations', 'id', 'stock_movements_from_location_id_fk')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations', 'id', 'stock_movements_to_location_id_fk')->nullOnDelete();
            $table->enum('movement_type', ['receipt', 'shipment', 'transfer', 'adjustment',
                'adjustment_in', 'adjustment_out', 'opening',
                'return_in', 'return_out', 'reservation', 'reservation_release',
                'write_off', 'cycle_count',
            ]);
            $table->nullableMorphs('reference'); // link to PO line, GRN line, shipment line, etc.
            $table->foreignId('uom_id')->constrained('units_of_measure', 'id', 'stock_movements_uom_id_fk');
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->nullable(); // For receipt/shipment valuation
            $table->decimal('total_cost', 20, 6)->storedAs('quantity * unit_cost'); // Computed at application level
            $table->foreignId('performed_by')->nullable()->constrained('users', 'id', 'stock_movements_performed_by_fk')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->index(['tenant_id', 'product_id', 'performed_at'], 'stock_movements_tenant_product_date_idx');
            $table->index(['tenant_id', 'reference_type', 'reference_id'], 'stock_movements_tenant_ref_idx');
            $table->index(['tenant_id', 'from_location_id', 'performed_at'], 'stock_movements_tenant_from_loc_date_idx');
            $table->index(['tenant_id', 'to_location_id', 'performed_at'], 'stock_movements_tenant_to_loc_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
