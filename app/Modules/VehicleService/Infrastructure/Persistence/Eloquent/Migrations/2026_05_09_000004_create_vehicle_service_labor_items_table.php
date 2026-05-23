<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_labor_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->restrictOnDelete();
            $table->foreignId('combo_item_id')->nullable()->constrained('combo_items', 'id')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id')->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('unit_cost', 20, 4)->nullable();

            // Discount stored as both configuration and calculated amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)
                  ->storedAs('quantity * unit_price')
                  ->comment('Gross = qty * unit price');
            $table->decimal('line_total', 20, 4)
                  ->storedAs('quantity * unit_price - discount_amount')
                  ->comment('Net after discount before tax');

            // Tax
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups', 'id')->nullOnDelete();
            $table->decimal('tax_amount', 20, 4)->default(0)->comment('Calculated tax amount');

            // Stored line total including tax
            $table->decimal('line_total_with_tax', 20, 4)
                  ->storedAs('quantity * unit_price - discount_amount + tax_amount')
                  ->comment('total including tax');

            // Incentive stored as both configuration and calculated amount
            $table->string('incentive_type')->nullable()->comment('percentage, fixed');
            $table->decimal('incentive_value', 20, 4)->default(0);
            $table->decimal('incentive_amount', 20, 4)->default(0)->comment('Calculated incentive amount');

            // lines account
            $table->foreignId('account_id')->nullable()->constrained('accounts', 'id')->nullOnDelete()->comment('account for posting');

            $table->timestamps();

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_labor_items_job_card_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_labor_items');
    }
};
