<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('purchase_order_id')->constrained(null, 'id', 'purchase_order_lines_purchase_order_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('ordered_qty', 20, 6);
            $table->decimal('received_qty', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount_pct', 10, 6)->default(0);
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('line_total', 20, 6)->storedAs('(ordered_qty * unit_price) * (1 - discount_pct/100)');
            // Purchase order lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id', 'purchase_order_lines_account_id_fk')->nullOnDelete(); // expense/asset account for posting

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
