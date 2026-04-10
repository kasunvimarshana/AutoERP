<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('from_bin_id')->nullable()->constrained('warehouse_bins');
            $table->foreignId('to_bin_id')->nullable()->constrained('warehouse_bins');
            $table->enum('movement_type', [
                'receipt', 'issue', 'transfer', 'adjustment', 
                'return_in', 'return_out', 'scrap', 'damage'
            ]);
            $table->morphs('reference'); // PO, SO, Return, etc.
            $table->decimal('quantity_in', 15, 4)->default(0);
            $table->decimal('quantity_out', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('serial_id')->nullable()->constrained('serials');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }
};