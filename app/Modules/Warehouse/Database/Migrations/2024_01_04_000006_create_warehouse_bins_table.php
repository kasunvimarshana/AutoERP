<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('warehouse_bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_id')->constrained('warehouse_shelves');
            $table->string('name');
            $table->string('code');
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['shelf_id', 'code']);
        });
    }
};