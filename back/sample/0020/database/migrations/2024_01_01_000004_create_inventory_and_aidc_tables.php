<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('uom'); // Unit of Measure
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->string('warehouse_location_id')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->enum('type', ['IN', 'OUT', 'ADJUSTMENT', 'TRANSFER']);
            $table->string('batch_id')->nullable();
            $table->string('serial_id')->nullable();
            $table->string('reference_type')->nullable(); // e.g. PurchaseOrder, SalesInvoice
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::create('aidc_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('identifier_value')->index(); // Barcode, QR, RFID tag
            $table->string('linkable_type'); // Product, StockMovement, Batch
            $table->unsignedBigInteger('linkable_id');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('aidc_identifiers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
    }
};
