<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('serial_number')->unique();
            $table->foreignId('batch_id')->nullable()->constrained();
            $table->foreignId('lot_id')->nullable()->constrained();
            $table->enum('status', ['available', 'sold', 'damaged', 'lost', 'returned'])->default('available');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
};