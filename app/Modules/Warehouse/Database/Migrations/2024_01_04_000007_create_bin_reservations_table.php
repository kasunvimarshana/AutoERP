<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('bin_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_id')->constrained('warehouse_bins');
            $table->foreignId('product_id')->constrained();
            $table->boolean('is_reserved')->default(false);
            $table->timestamps();
            $table->unique(['bin_id', 'product_id']);
        });
    }
};