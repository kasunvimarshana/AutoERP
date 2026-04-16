<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number');
            $table->foreignId('from_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            // $table->boolean('is_inter_org')->default(false); // flag for cross‑OrgUnit transfers
            // $table->foreignId('internal_sales_order_id')->nullable()->constrained('sales_orders'); // for inter‑OrgUnit trading
            // $table->foreignId('internal_purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->enum('status', ['draft', 'pending', 'in_transit', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_number']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure');
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