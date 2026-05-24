<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders', 'id')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id')->restrictOnDelete();
            $table->decimal('ordered_qty', 20, 4);
            $table->decimal('received_qty', 20, 4)->default(0);
            $table->decimal('rejected_qty', 20, 4)->default(0);
            $table->decimal('invoiced_qty', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4);

            // Discount stored as both configuration and calculated amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)
                  ->storedAs('ordered_qty * unit_price')
                  ->comment('Gross = quantity * unit price');
            $table->decimal('line_total', 20, 4)
                  ->storedAs('ordered_qty * unit_price - discount_amount')
                  ->comment('Net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0)->comment('Calculated tax amount');

            // Stored line total including tax
            $table->decimal('line_total_with_tax', 20, 4)
                  ->storedAs('ordered_qty * unit_price - discount_amount + tax_amount')
                  ->comment('total including tax');

            // Line posting account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete()->comment('Account used for posting this line');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'purchase_order_id'], 'purchase_order_lines_order_idx');
            $table->index(['tenant_id', 'item_id', 'variant_id'], 'purchase_order_lines_item_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
