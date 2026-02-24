<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_valuation_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('product_id')->index();

            // receipt | deduction | adjustment
            $table->string('movement_type', 20);

            // Signed quantity of this entry (always positive; sign conveyed by movement_type)
            $table->decimal('qty', 18, 8);

            // Unit cost at the time of recording (BCMath DECIMAL(18,8))
            $table->decimal('unit_cost', 18, 8);

            // Signed total value impact: positive = stock in, negative = stock out
            $table->decimal('total_value', 18, 8);

            // Running balance for the product after this entry
            $table->decimal('running_balance_qty',   18, 8)->default(0);
            $table->decimal('running_balance_value', 18, 8)->default(0);

            // Costing method used for this entry
            $table->string('valuation_method', 20)->default('weighted_average');

            // Optional polymorphic reference to the originating document
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();

            $table->timestamps();

            // Composite index for efficient per-product ledger queries
            $table->index(['tenant_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_valuation_entries');
    }
};
