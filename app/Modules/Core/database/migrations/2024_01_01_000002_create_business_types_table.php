<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // pharmacy, manufacturing, ecommerce, etc.
            $table->string('code')->unique();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }
};