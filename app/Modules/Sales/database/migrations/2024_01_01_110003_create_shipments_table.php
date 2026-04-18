<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'shipments_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('customer_id');
            $table->foreignId('sales_order_id')->nullable()->constrained(null, 'id', 'shipments_sales_order_id_fk')->nullOnDelete(); // nullable for SMB direct sell
            $table->foreignId('warehouse_id');
            $table->string('shipment_number');
            $table->enum('status', ['draft', 'picking', 'packed', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->date('shipped_date')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('currency_id')->constrained('currencies', 'id', 'shipments_currency_id_fk');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'shipment_number'], 'shipments_tenant_shipment_uk');
        });

        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained(null, 'id', 'shipment_lines_shipment_id_fk')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines', 'id', 'shipment_lines_sales_order_line_id_fk')->nullOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('serial_id')->nullable();
            $table->foreignId('from_location_id');
            $table->foreignId('uom_id');
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
