<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bill_of_materials')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->onDelete('set null');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('scrap_percentage', 5, 2)->default(0);
            $table->integer('sequence')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['bom_id', 'product_id']);
            $table->index('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
    }
};
