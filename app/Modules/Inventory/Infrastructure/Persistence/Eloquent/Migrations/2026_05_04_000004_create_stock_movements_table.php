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

            $table->string('direction')->comment('in, out');
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            // $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            // $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('txn_type')->comment('movement_type: GRN, GDN, Adjustment');
            $table->nullableMorphs('reference'); // link to PO line, GRN line, shipment line, etc.
            $table->foreignId('uom_id')->constrained('unit_of_measures');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4)->nullable(); // For receipt/shipment valuation
            // $table->decimal('total_cost', 20, 4)->storedAs('quantity * unit_cost')->comment('quantity * unit_cost');
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
