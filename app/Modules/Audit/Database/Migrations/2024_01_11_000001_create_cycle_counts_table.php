<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('cycle_count_number')->unique();
            $table->enum('method', ['blind', 'scheduled', 'spot_check', 'continuous'])->default('scheduled');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'completed', 'adjusted'])->default('open');
            $table->integer('accuracy_percentage')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }
};