<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'sales_return_lines_tenant_fk')->restrictOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sales_return_id');
            $table->foreignId('item_id')->nullable();
            $table->foreignId('item_variant_id')->nullable();
            $table->foreignId('uom_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->decimal('returned_quantity', 20, 6);
            $table->decimal('source_quantity', 20, 6)->default('0.000000');
            $table->decimal('previously_returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_price', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6)->default('0.000000');
            $table->foreignId('inventory_movement_id')->nullable();
            $table->string('condition_status')->default('sellable');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'sales_return_lines_scope_ix');
            $table->index(['source_line_type', 'source_line_id'], 'sales_return_lines_source_ix');

            $table->unique(['id', 'tenant_id'], 'sales_return_lines_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_return_lines_organization_unit_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_return_id', 'tenant_id'], 'sales_return_lines_sales_return_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_returns')
                ->cascadeOnDelete();
            $table->foreign(['item_id', 'tenant_id'], 'sales_return_lines_item_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('items')
                ->restrictOnDelete();
            $table->foreign(['item_variant_id', 'tenant_id'], 'sales_return_lines_item_variant_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('item_variants')
                ->restrictOnDelete();
            $table->foreign(['uom_id', 'tenant_id'], 'sales_return_lines_uom_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('unit_of_measures')
                ->restrictOnDelete();
            $table->foreign(['inventory_movement_id', 'tenant_id'], 'sales_return_lines_inventory_movement_id_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('inventory_movements')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_lines');
    }
};
