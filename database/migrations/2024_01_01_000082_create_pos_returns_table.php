<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // POS Return Transactions (link back to original POS transaction)
        Schema::create('pos_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignUuid('business_location_id')->constrained('business_locations')->cascadeOnDelete();
            $table->foreignUuid('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->decimal('total_refund', 20, 8)->default(0);
            $table->string('refund_method')->default('cash'); // cash, store_credit, original_payment
            $table->string('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference_no']);
            $table->index(['tenant_id', 'pos_transaction_id']);
        });

        // POS Return Lines
        Schema::create('pos_return_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pos_return_id')->constrained('pos_returns')->cascadeOnDelete();
            $table->foreignUuid('pos_transaction_line_id')->constrained('pos_transaction_lines')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 20, 8);
            $table->decimal('unit_price', 20, 8);
            $table->decimal('refund_amount', 20, 8);
            $table->boolean('restock')->default(true); // whether to put back into inventory
            $table->timestamps();

            $table->index('pos_return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_lines');
        Schema::dropIfExists('pos_returns');
    }
};
