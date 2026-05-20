<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdn_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('reference')->nullable();
            $table->foreignId('gdn_header_id')->constrained('gdn_headers', 'id')->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->nullable()->constrained('sales_order_lines', 'id')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses', 'id')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id')->nullOnDelete();

            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id')->cascadeOnDelete();
            $table->decimal('delivered_qty', 20, 4);
            $table->decimal('rejected_qty', 20, 4)->default(0);
            $table->decimal('invoiced_qty', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('unit_cost', 20, 4)->nullable();

            // Discount – stored both as configuration and as absolute amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)
                  ->storedAs('delivered_qty * unit_price')
                  ->comment('Gross = qty * unit price');
            $table->decimal('line_total', 20, 4)
                  ->storedAs('gross_amount - discount_amount')
                  ->comment('Net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0)->comment('Calculated tax amount');

            // Optional – if you want a stored line total including tax
            $table->decimal('line_total_with_tax', 20, 4)
                  ->storedAs('line_total + tax_amount')
                  ->comment('total including tax');

            // lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete()->comment('account for posting');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdn_lines');
    }
};
