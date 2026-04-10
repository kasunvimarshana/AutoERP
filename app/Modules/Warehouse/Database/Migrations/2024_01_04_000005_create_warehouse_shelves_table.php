<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('warehouse_shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('warehouse_racks');
            $table->string('name');
            $table->string('code');
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['rack_id', 'code']);
        });
    }
};