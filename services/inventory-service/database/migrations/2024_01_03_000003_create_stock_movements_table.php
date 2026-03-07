<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('inventory_id')->index();
            $table->uuid('product_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment', 'reservation', 'release']);
            $table->integer('quantity');
            $table->unsignedInteger('previous_quantity');
            $table->unsignedInteger('new_quantity');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->uuid('performed_by')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'product_id', 'type']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);

            $table->foreign('inventory_id')
                  ->references('id')
                  ->on('inventory')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
