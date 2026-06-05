<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grn_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('grn_header_id')->constrained('grn_headers', 'id')->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->nullable()->constrained('purchase_order_lines', 'id')->nullOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->restrictOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses', 'id')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id')->nullOnDelete();

            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id')->restrictOnDelete();
            $table->decimal('expected_qty', 20, 4)->default(0);
            $table->decimal('received_qty', 20, 4);
            $table->decimal('accepted_qty', 20, 4)->default(0);
            $table->decimal('rejected_qty', 20, 4)->default(0);
            $table->decimal('damaged_qty', 20, 4)->default(0);
            $table->decimal('inspected_qty', 20, 4)->default(0);
            $table->decimal('putaway_qty', 20, 4)->default(0);
            $table->decimal('returned_qty', 20, 4)->default(0);
            $table->decimal('invoiced_qty', 20, 4)->default(0);
            $table->decimal('unit_price', 20, 4);

            // Discount stored as both configuration and calculated amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)->default(0)
                ->comment('Application-calculated gross = quantity * unit price');
            $table->decimal('line_total', 20, 4)->default(0)
                ->comment('Application-calculated net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0)->comment('Calculated tax amount');

            // Application-calculated line total including tax
            $table->decimal('line_total_with_tax', 20, 4)->default(0)
                ->comment('Application-calculated total including tax');

            // Line posting account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete()->comment('Account used for posting this line');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'grn_header_id'], 'grn_lines_header_idx');
            $table->index(['tenant_id', 'grn_header_id', 'accepted_qty'], 'grn_lines_header_accepted_idx');
            $table->index(['tenant_id', 'purchase_order_line_id'], 'grn_lines_purchase_order_line_idx');
            $table->index(['tenant_id', 'item_id', 'variant_id'], 'grn_lines_item_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_lines');
    }
};
