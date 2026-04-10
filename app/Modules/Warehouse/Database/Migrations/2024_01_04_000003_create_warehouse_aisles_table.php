<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('warehouse_aisles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones');
            $table->string('name');
            $table->string('code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['zone_id', 'code']);
        });
    }
};