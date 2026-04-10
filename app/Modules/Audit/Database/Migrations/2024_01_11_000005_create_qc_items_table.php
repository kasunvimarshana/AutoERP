<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('qc_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_check_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_checked', 15, 4);
            $table->decimal('quantity_passed', 15, 4);
            $table->decimal('quantity_failed', 15, 4)->default(0);
            $table->json('defects')->nullable(); // e.g., ["broken", "wrong_color"]
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }
};