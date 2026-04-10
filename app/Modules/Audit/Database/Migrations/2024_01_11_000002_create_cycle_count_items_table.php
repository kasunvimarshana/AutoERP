<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('cycle_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_count_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->foreignId('serial_id')->nullable()->constrained('serials');
            $table->decimal('expected_quantity', 15, 4);
            $table->decimal('counted_quantity', 15, 4)->nullable();
            $table->decimal('variance', 15, 4)->nullable();
            $table->decimal('variance_percentage', 5, 2)->nullable();
            $table->enum('status', ['pending', 'counted', 'adjusted', 'investigated'])->default('pending');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};