<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sales_quotation_id');
            $table->unsignedInteger('line_number');
            $table->foreignId('item_id');
            $table->foreignId('item_variant_id')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uom_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('line_subtotal', 20, 6);
            $table->string('discount_calculation_type')->nullable();
            $table->decimal('discount_rate', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->string('tax_calculation_type')->nullable();
            $table->decimal('tax_rate', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->string('charge_calculation_type')->nullable();
            $table->decimal('charge_rate', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->string('status')->default('open');
            $table->timestamps();

            $table->unique(['sales_quotation_id', 'line_number'], 'sales_quotation_lines_number_uk');
            $table->index(['tenant_id', 'organization_unit_id'], 'sales_quotation_lines_scope_idx');

            $table->unique(['id', 'tenant_id'], 'sales_quotation_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_quotation_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_quotation_id', 'tenant_id'], 'sales_quotation_lines_sales_quotation_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_quotations')
                ->cascadeOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'sales_quotation_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'sales_quotation_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'sales_quotation_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_lines');
    }
};
