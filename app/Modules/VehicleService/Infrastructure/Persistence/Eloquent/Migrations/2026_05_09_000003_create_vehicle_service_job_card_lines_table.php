<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_job_card_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->restrictOnDelete();
            $table->string('line_type')->default('inventory')->comment('inventory, labor, external_service, customer_supplied');
            $table->unsignedBigInteger('combo_group_key')->nullable()->comment('Groups exploded combo component lines');
            $table->foreignId('combo_parent_line_id')->nullable()->constrained('vehicle_service_job_card_lines', 'id')->nullOnDelete();
            $table->foreignId('combo_item_id')->nullable()->constrained('combo_items', 'id')->nullOnDelete();
            $table->boolean('is_combo_component')->default(false);
            $table->foreignId('variant_id')->nullable()->constrained('item_variants', 'id')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches', 'id')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials', 'id')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses', 'id')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id')->nullOnDelete();
            $table->boolean('is_customer_supplied')->default(false);
            $table->boolean('is_external_service')->default(false);
            $table->boolean('requires_stock_movement')->default(true);
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id')->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('quantity_base', 20, 4)->default(0);
            $table->decimal('reserved_qty', 20, 4)->default(0);
            $table->decimal('consumed_qty', 20, 4)->default(0);
            $table->decimal('returned_qty', 20, 4)->default(0);
            $table->decimal('cancelled_qty', 20, 4)->default(0);
            $table->decimal('outstanding_qty', 20, 4)->default(0);
            $table->string('inventory_status')->default('pending')->comment('pending, reserved, consumed, returned, cancelled');
            $table->decimal('unit_price', 20, 4);
            $table->decimal('unit_cost', 20, 4)->nullable();

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

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_job_card_lines_job_card_idx');
            $table->index(['tenant_id', 'line_type'], 'vehicle_service_job_card_lines_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_job_card_lines');
    }
};
