<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // USD, EUR, INR
            $table->string('name');
            $table->string('symbol');
            $table->integer('decimal_places')->default(2);
            $table->timestamps();
        });
    }
};