<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('warehouse_id');
            $table->uuid('location_id')->nullable();
            // purchase_receipt, sale_shipment, transfer_in, transfer_out,
            // adjustment_in, adjustment_out, return_in, return_out, opening
            $table->string('type', 50);
            $table->string('reference_type', 100)->nullable(); // morphable source
            $table->string('reference_id', 36)->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->string('unit_of_measure', 30)->default('piece');
            $table->decimal('quantity_before', 15, 4)->default(0);
            $table->decimal('quantity_after', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
