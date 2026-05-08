<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_non_inventory_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->foreignId('job_card_id')->constrained('service_job_cards', 'id', 'service_job_card_lines_job_card_id_fk')->cascadeOnDelete();
            // $table->foreignId('product_id');
            // $table->foreignId('variant_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('uom_id');
            $table->decimal('quantity', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('unit_cost', 20, 6);

            $table->boolean('is_billable')->default(true);

            // Discount – stored both as configuration and as absolute amount
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 6)->default(0);
            $table->decimal('discount_amount', 20, 6)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 6)
                  ->storedAs('quantity * unit_price')
                  ->comment('Gross = qty * unit price');
            $table->decimal('line_total', 20, 6)
                  ->storedAs('gross_amount - discount_amount')
                  ->comment('Net after discount before tax');
            $table->decimal('total_cost', 20, 6)
                  ->storedAs('quantity * unit_cost')
                  ->comment('Total cost = qty * unit cost');

            // Tax
            $table->foreignId('tax_group_id')->nullable();
            $table->decimal('tax_amount', 20, 6)->default(0)
                  ->comment('Calculated tax amount; line_total_with_tax = line_total + tax_amount');

            // Optional – if you want a stored line total including tax
            $table->decimal('line_total_with_tax', 20, 6)
                  ->storedAs('line_total + tax_amount')
                  ->comment('total including tax');

            // Service job card lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id', 'service_job_card_lines_account_id_fk')->nullOnDelete()->comment('income/asset account for posting');

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('units_of_measure');
            $table->foreign('tax_group_id')->references('id')->on('tax_groups')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('serial_id')->references('id')->on('serials')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('warehouse_locations')->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_non_inventory_items');
    }
};
