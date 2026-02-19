<?php

declare(strict_types=1);

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
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out', 'transfer', 'adjustment'])->comment('Type of stock movement');
            $table->integer('quantity')->comment('Positive for in/additions, negative for out/deductions');
            $table->decimal('unit_cost', 10, 2)->nullable()->comment('Cost at time of movement');
            $table->string('reference_type')->nullable()->comment('Polymorphic type: PurchaseOrder, JobCard, etc.');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('Polymorphic ID');
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->comment('Source branch for transfers');
            $table->foreignId('to_branch_id')->nullable()->constrained('branches')->comment('Destination branch for transfers');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            // Indexes for performance
            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
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
