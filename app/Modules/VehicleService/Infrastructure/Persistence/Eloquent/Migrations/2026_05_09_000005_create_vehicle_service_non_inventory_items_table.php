<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_service_non_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('reference')->nullable();
            $table->foreignId('job_card_id')->constrained('vehicle_service_job_cards')->cascadeOnDelete();
            $table->string('source_type')->default('internal')->comment('internal, external_service, customer_supplied');
            $table->boolean('is_billable')->default(true);
            $table->string('name');
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

            $table->index(['tenant_id', 'job_card_id'], 'vehicle_service_non_inventory_items_job_card_idx');
            $table->index(['tenant_id', 'source_type'], 'vehicle_service_non_inventory_items_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_non_inventory_items');
    }
};
