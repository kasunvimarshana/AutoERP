<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_uom_id')->constrained('uoms');
            $table->foreignId('to_uom_id')->constrained('uoms');
            $table->decimal('multiplier', 18, 6);
            $table->timestamps();
            $table->unique(['from_uom_id', 'to_uom_id']);
        });
    }
};