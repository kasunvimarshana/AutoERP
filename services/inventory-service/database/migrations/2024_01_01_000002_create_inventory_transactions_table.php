<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('product_id');

            $table->enum('type', ['receipt', 'adjustment', 'reservation', 'release', 'sale']);

            // Positive = increase, negative = decrease
            $table->integer('quantity');
            $table->unsignedInteger('previous_quantity');
            $table->unsignedInteger('new_quantity');

            // Optional reference to external entity (e.g., order_id)
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();

            $table->text('notes')->nullable();
            $table->string('performed_by', 255)->nullable();

            // Transactions are immutable; no updated_at
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('inventory_id');
            $table->index('product_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['inventory_id', 'type']);
            $table->index(['reference_type', 'reference_id']);

            $table->foreign('inventory_id')
                  ->references('id')
                  ->on('inventories')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
