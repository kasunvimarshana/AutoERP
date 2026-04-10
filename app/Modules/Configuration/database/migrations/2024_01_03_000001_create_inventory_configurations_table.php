<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('inventory_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses');
            
            // Valuation Methods
            $table->enum('valuation_method', ['FIFO', 'LIFO', 'AVCO', 'WAC'])->default('FIFO');
            
            // Inventory Management Methods
            $table->enum('management_method', ['periodic', 'perpetual'])->default('perpetual');
            
            // Stock Rotation
            $table->enum('rotation_strategy', ['FIFO', 'LIFO', 'FEFO', 'LEFO'])->default('FIFO');
            
            // Allocation Algorithm
            $table->enum('allocation_algorithm', ['FIFO', 'CLOSEST_LOCATION', 'RANDOM', 'CUSTOM'])->default('FIFO');
            
            // Cycle Counting
            $table->enum('cycle_count_method', ['blind', 'scheduled', 'spot_check', 'continuous'])->default('scheduled');
            $table->integer('cycle_count_frequency_days')->default(30);
            
            // QC and Returns
            $table->boolean('require_qc_on_receipt')->default(true);
            $table->boolean('require_qc_on_return')->default(true);
            $table->integer('return_window_days')->default(30);
            
            // Low Stock
            $table->boolean('auto_reorder')->default(false);
            
            // GS1 Compatibility
            $table->boolean('enable_gs1')->default(false);
            
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
};