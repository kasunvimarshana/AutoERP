<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('valuation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('warehouse_id')->nullable()->constrained();
            $table->date('report_date');
            $table->enum('method', ['FIFO', 'LIFO', 'AVCO', 'WAC'])->default('FIFO');
            $table->decimal('total_quantity', 15, 4);
            $table->decimal('total_value', 15, 2);
            $table->timestamps();
        });
    }
};