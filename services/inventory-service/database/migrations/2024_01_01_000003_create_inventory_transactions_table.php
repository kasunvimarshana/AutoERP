<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('inventory_item_id')
                  ->constrained('inventory_items')
                  ->cascadeOnDelete();

            // Transaction type: add, subtract, set, reserve, release, adjustment
            $table->string('type', 30);

            // Quantity before / change / after
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_change')->default(0);
            $table->integer('quantity_after')->default(0);

            // Reserved quantity before / change / after
            $table->integer('reserved_before')->default(0);
            $table->integer('reserved_change')->default(0);
            $table->integer('reserved_after')->default(0);

            $table->string('reason', 500)->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->jsonb('metadata')->nullable();

            // Immutable — no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['tenant_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
