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
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            // $table->string('transaction_no')->nullable();
            // $table->date('transaction_date')->nullable();
            $table->string('direction')->comment('IN, OUT');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            // $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            // $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('txn_type')->comment('movement_type: GRN, GDN, Adjustment (OPENING_STOCK, PURCHASE_RECEIPT, PURCHASE_RETURN, SALES_ISSUE, SALES_RETURN, STOCK_TRANSFER_IN, STOCK_TRANSFER_OUT, ADJUSTMENT_IN, ADJUSTMENT_OUT, PRODUCTION_CONSUMPTION, PRODUCTION_OUTPUT, SCRAP, DAMAGE, COUNT_ADJUSTMENT)');
            $table->nullableMorphs('reference'); // Link to PO line, GRN line, shipment line, etc.
            $table->foreignId('uom_id')->constrained('unit_of_measures');
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('quantity_in', 20, 4)->default(0);
            $table->decimal('quantity_out', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->nullable(); // For receipt/shipment valuation
            // $table->decimal('total_cost', 20, 4)->default(0)->comment('Application-calculated: quantity * unit_cost');
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->decimal('balance_quantity', 20, 4)->default(0);
            $table->decimal('balance_value', 20, 4)->default(0);
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'item_id', 'performed_at'], 'stock_movements_item_performed_at_idx');
            $table->index(['tenant_id', 'reference_type', 'reference_id'], 'stock_movements_reference_idx');
            $table->index(['tenant_id', 'location_id', 'performed_at'], 'stock_movements_location_performed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
