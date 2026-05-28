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
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('direction')->comment('IN, OUT');
            $table->string('movement_type')->comment(
                'OPENING_BALANCE, PURCHASE_RECEIPT, SALES_ISSUE, TRANSFER_OUT, TRANSFER_IN, '
                . 'ADJUSTMENT_IN, ADJUSTMENT_OUT, RESERVATION, RESERVATION_RELEASE, RETURN_IN, '
                . 'RETURN_OUT, STOCK_COUNT_GAIN, STOCK_COUNT_LOSS'
            );
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->nullableMorphs('source');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('transaction_uom_id')->constrained('unit_of_measures');
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->decimal('quantity', 20, 4)->default(0)->comment('Transaction quantity in transaction_uom_id');
            $table->decimal('base_quantity', 20, 4)->default(0);
            $table->decimal('quantity_in', 20, 4)->default(0)->comment('Transaction quantity received');
            $table->decimal('quantity_out', 20, 4)->default(0)->comment('Transaction quantity issued');
            $table->decimal('base_quantity_in', 20, 4)->default(0);
            $table->decimal('base_quantity_out', 20, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->nullable(); // For receipt/shipment valuation
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->decimal('balance_quantity', 20, 4)->default(0);
            $table->decimal('balance_value', 20, 4)->default(0);
            $table->string('status')->default('POSTED')->comment('DRAFT, POSTED, REVERSED, CANCELLED');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'item_id', 'performed_at'], 'stock_movements_item_performed_at_idx');
            $table->index(['tenant_id', 'source_type', 'source_id'], 'stock_movements_reference_idx');
            $table->index(['tenant_id', 'location_id', 'performed_at'], 'stock_movements_location_performed_at_idx');
            $table->index(['tenant_id', 'status', 'movement_type'], 'stock_movements_status_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
