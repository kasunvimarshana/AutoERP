<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('name');
            $table->string('code');
            $table->enum('type', ['storage', 'receiving', 'picking', 'shipping', 'returns', 'qc', 'damage', 'overstock'])->default('storage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });
    }
};