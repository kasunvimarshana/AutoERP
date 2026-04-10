<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('input_type', ['text', 'dropdown', 'multiselect', 'color', 'size'])->default('text');
            $table->json('options')->nullable(); // For dropdown/multiselect
            $table->timestamps();
        });
    }
};