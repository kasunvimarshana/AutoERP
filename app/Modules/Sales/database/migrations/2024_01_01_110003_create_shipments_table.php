<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete(); // nullable for SMB direct sell
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('shipment_number');
            $table->enum('status', ['draft', 'picking', 'packed', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->date('shipped_date')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'shipment_number'], 'uq_shipments_tenant_shipment');
        });

        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_location_id')->constrained('warehouse_locations')->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained('units_of_measure');
            $table->decimal('shipped_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
        Schema::dropIfExists('shipments');
    }
};