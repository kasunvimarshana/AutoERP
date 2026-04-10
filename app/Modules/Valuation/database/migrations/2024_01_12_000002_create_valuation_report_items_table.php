<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Inspiration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('valuation_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valuation_report_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_value', 15, 2);
            $table->timestamps();
        });
    }
};