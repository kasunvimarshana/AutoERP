<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('transaction_type'); // purchase_receipt/sales_shipment/internal_transfer/adjustment/return
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('total_cost', 20, 4);
            $table->string('batch_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at');
            $table->unsignedBigInteger('transacted_by')->nullable();
            $table->boolean('is_pharmaceutical_compliant')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
