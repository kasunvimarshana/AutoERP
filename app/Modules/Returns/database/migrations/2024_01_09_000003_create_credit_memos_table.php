<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('credit_memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('return_id')->constrained();
            $table->string('memo_number')->unique();
            $table->date('memo_date');
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['draft', 'issued', 'applied', 'cancelled'])->default('draft');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};