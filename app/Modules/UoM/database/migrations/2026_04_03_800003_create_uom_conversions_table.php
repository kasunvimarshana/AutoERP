<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_uom_id');
            $table->unsignedBigInteger('to_uom_id');
            $table->decimal('factor', 15, 8);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['from_uom_id', 'to_uom_id', 'product_id']);
            $table->foreign('from_uom_id')->references('id')->on('units_of_measure');
            $table->foreign('to_uom_id')->references('id')->on('units_of_measure');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
