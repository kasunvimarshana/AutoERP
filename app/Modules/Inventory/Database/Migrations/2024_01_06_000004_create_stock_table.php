<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('bin_id')->nullable()->constrained('warehouse_bins');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('serial_id')->nullable()->constrained('serials');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->decimal('available_quantity', 15, 4)->virtualAs('quantity - reserved_quantity');
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_id', 'batch_id', 'lot_id', 'serial_id']);
        });
    }
};