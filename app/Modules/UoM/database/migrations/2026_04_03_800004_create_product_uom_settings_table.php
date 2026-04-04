<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_uom_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->unsignedBigInteger('base_uom_id');
            $table->unsignedBigInteger('purchase_uom_id')->nullable();
            $table->unsignedBigInteger('sales_uom_id')->nullable();
            $table->unsignedBigInteger('inventory_uom_id')->nullable();
            $table->decimal('purchase_factor', 15, 8)->default(1);
            $table->decimal('sales_factor', 15, 8)->default(1);
            $table->decimal('inventory_factor', 15, 8)->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('base_uom_id')->references('id')->on('units_of_measure');
            $table->foreign('purchase_uom_id')->references('id')->on('units_of_measure');
            $table->foreign('sales_uom_id')->references('id')->on('units_of_measure');
            $table->foreign('inventory_uom_id')->references('id')->on('units_of_measure');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_uom_settings');
    }
};
