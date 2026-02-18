<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('transaction_id');
            $table->uuid('product_id');
            $table->uuid('variation_id')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->string('discount_type')->nullable(); // percentage, fixed
            $table->uuid('tax_rate_id')->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('pos_transactions')->onDelete('cascade');
            $table->foreign('tax_rate_id')->references('id')->on('pos_tax_rates')->onDelete('set null');
            $table->index(['tenant_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_lines');
    }
};
