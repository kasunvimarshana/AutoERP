<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('pick_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pick_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->constrained();
            $table->foreignId('bin_id')->nullable()->constrained('warehouse_bins');
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('serial_id')->nullable()->constrained('serials');
            $table->decimal('quantity_required', 15, 4);
            $table->decimal('quantity_picked', 15, 4)->default(0);
            $table->timestamps();
        });
    }
};