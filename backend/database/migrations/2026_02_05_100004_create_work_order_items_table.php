<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('bom_item_id')->nullable()->constrained('bom_items')->onDelete('set null');
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('allocated_quantity', 15, 4)->default(0);
            $table->decimal('consumed_quantity', 15, 4)->default(0);
            $table->decimal('returned_quantity', 15, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units_of_measure')->onDelete('set null');
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('scrap_percentage', 5, 2)->default(0);
            $table->enum('status', ['pending', 'allocated', 'consumed', 'completed'])->default('pending');
            $table->integer('sequence')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['work_order_id', 'product_id']);
            $table->index('status');
            $table->index('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
