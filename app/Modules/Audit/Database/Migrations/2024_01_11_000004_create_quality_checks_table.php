<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->morphs('checkable'); // GoodsReceipt, Return, etc.
            $table->string('qc_number')->unique();
            $table->date('check_date');
            $table->enum('result', ['pass', 'fail', 'partial'])->default('pass');
            $table->json('parameters')->nullable(); // Defects, color, weight, etc.
            $table->string('notes')->nullable();
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamps();
        });
    }
};