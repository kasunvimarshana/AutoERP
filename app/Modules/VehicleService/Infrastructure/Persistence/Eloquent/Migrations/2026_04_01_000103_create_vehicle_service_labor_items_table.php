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
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->string('reference')->nullable();
            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items', 'id')->cascadeOnDelete();
            $table->foreignId('combo_item_id')->nullable()->constrained('combo_items', 'id')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->constrained('unit_of_measures', 'id')->cascadeOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('unit_cost', 20, 4);

            // Discount – stored both as configuration and as absolute amount
            $table->string('discount_type')->nullable()->comment('percentage, fixed');
            $table->decimal('discount_value', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0)->comment('Calculated discount amount');

            // Line net (before tax)
            $table->decimal('gross_amount', 20, 4)
                  ->storedAs('quantity * unit_price')
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

            // Incentive – stored both as configuration and as absolute amount
            $table->string('incentive_type')->nullable()->comment('percentage, fixed');
            $table->decimal('incentive_value', 20, 4)->default(0);
            $table->decimal('ncentive_amount', 20, 4)->default(0)->comment('Calculated incentive amount');

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
