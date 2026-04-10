<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('purchase_order_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('gr_number')->unique();
            $table->date('receipt_date');
            $table->enum('status', ['pending', 'qc_pending', 'qc_pass', 'qc_fail', 'putaway_pending', 'completed'])->default('pending');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }
};