<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // kg, g, lb, meter, etc.
            $table->foreignId('category_id')->constrained('uom_categories');
            $table->enum('level', ['base', 'derivative'])->default('derivative');
            $table->decimal('conversion_factor', 18, 6)->default(1); // Relative to base
            $table->string('symbol')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['category_id', 'code']);
        });
    }
};