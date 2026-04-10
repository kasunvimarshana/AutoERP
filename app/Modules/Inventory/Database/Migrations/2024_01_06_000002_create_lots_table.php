<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->string('lot_number')->unique();
            $table->date('created_date');
            $table->date('expiry_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'lot_number']);
        });
    }
};