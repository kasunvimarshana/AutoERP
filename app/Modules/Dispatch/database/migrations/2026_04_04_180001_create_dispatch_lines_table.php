<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('dispatch_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('dispatches')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id'); $table->decimal('quantity_dispatched',15,4);
            $table->string('batch_number')->nullable(); $table->string('lot_number')->nullable(); $table->string('serial_number')->nullable();
            $table->index('dispatch_id');
        });
    }
    public function down(): void { Schema::dropIfExists('dispatch_lines'); }
};
