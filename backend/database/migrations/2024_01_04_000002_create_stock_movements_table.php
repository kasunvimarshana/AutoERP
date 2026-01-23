<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('movement_number')->unique();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->enum('movement_type', ['purchase', 'sale', 'adjustment', 'transfer', 'return', 'damage', 'usage'])->default('purchase');
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('reference_type')->nullable(); // Job card, purchase order, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('movement_date');
            $table->timestamps();

            $table->index(['tenant_id', 'movement_type']);
            $table->index('movement_number');
            $table->index(['inventory_item_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
