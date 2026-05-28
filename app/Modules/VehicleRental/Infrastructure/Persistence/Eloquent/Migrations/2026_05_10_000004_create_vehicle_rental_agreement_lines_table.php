<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_rental_agreement_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('agreement_id')->constrained('vehicle_rental_agreements')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->unsignedInteger('line_number');
            $table->string('line_type')->comment('base_rental, driver, deposit, extra_charge, package, accessory');
            $table->string('charge_scope')->default('customer')->comment('customer, provider');
            $table->string('billing_basis')->comment('hour, day, week, month, year, km, fixed, package');
            $table->string('status')->default('draft');
            $table->string('description');
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('included_quantity', 20, 4)->default(0);
            $table->decimal('minimum_billable_quantity', 20, 4)->default(0);
            $table->decimal('unit_rate', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);
            $table->boolean('is_billable')->default(true);
            $table->boolean('is_payable')->default(false);
            $table->boolean('is_metered')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->unique(['agreement_id', 'line_number'], 'vehicle_rental_agreement_lines_number_uk');
            $table->index(['tenant_id', 'agreement_id'], 'vehicle_rental_agreement_lines_agreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_rental_agreement_lines');
    }
};
