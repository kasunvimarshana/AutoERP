<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('valuation_method')->default('fifo');
            $table->string('management_method')->default('standard');
            $table->string('stock_rotation_strategy')->default('fifo');
            $table->string('allocation_algorithm')->default('fifo');
            $table->string('cycle_count_method')->default('full');
            $table->boolean('negative_stock_allowed')->default(false);
            $table->boolean('auto_reorder_enabled')->default(false);
            $table->decimal('default_reorder_point', 15, 4)->nullable();
            $table->decimal('default_reorder_qty', 15, 4)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_settings');
    }
};
