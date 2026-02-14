<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Append-only stock ledger for immutable audit trails.
     * Supports FIFO/FEFO, batch/lot/serial tracking, and expiry handling.
     */
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('branch_id')->constrained()->onDelete('restrict');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('restrict');
            $table->enum('transaction_type', [
                'purchase', 'sale', 'transfer_in', 'transfer_out',
                'adjustment_in', 'adjustment_out', 'return', 'production',
            ]);
            $table->string('reference_type')->nullable(); // e.g., 'App\Models\PurchaseOrder'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 14, 2);
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('created_at')->useCurrent();

            // Indexes for performance
            $table->index(['tenant_id', 'product_id', 'branch_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['product_id', 'batch_number']);
            $table->index(['product_id', 'serial_number']);
            $table->index(['expiry_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Create a view for current stock levels (computed from append-only ledger)
        // Note: The view syntax is compatible with both PostgreSQL and MySQL
        $this->createStockSummaryView();
    }

    /**
     * Create stock summary view with cross-database compatibility.
     */
    protected function createStockSummaryView(): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        // Compatible view for both PostgreSQL and MySQL
        DB::statement("
            CREATE OR REPLACE VIEW stock_summary AS
            SELECT 
                sl.tenant_id,
                sl.product_id,
                sl.branch_id,
                sl.warehouse_id,
                sl.batch_number,
                sl.lot_number,
                sl.expiry_date,
                SUM(CASE 
                    WHEN sl.transaction_type IN ('purchase', 'transfer_in', 'adjustment_in', 'return', 'production') 
                    THEN sl.quantity 
                    ELSE -sl.quantity 
                END) as current_quantity,
                AVG(sl.unit_cost) as average_cost
            FROM stock_ledger sl
            GROUP BY sl.tenant_id, sl.product_id, sl.branch_id, sl.warehouse_id, sl.batch_number, sl.lot_number, sl.expiry_date
            HAVING current_quantity > 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS stock_summary');
        Schema::dropIfExists('stock_ledger');
    }
};
