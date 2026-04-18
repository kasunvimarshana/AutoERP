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
            $table->foreignId('purchase_order_id')->constrained(null, 'id', 'purchase_order_lines_purchase_order_id_fk')->cascadeOnDelete();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('ordered_qty', 15, 4);
            $table->decimal('received_qty', 15, 4)->default(0);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->foreignId('tax_class_id')->nullable();
            $table->decimal('line_total', 15, 4)->storedAs('(ordered_qty * unit_price) * (1 - discount_pct/100)');
            // Purchase order lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id', 'purchase_order_lines_account_id_fk')->nullOnDelete(); // expense/asset account for posting
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
