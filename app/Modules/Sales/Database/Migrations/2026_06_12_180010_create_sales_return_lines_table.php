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
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
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
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->string('condition_status')->default('sellable');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'sales_return_lines_scope_idx');
            $table->index(['source_line_type', 'source_line_id'], 'sales_return_lines_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_lines');
    }
};
