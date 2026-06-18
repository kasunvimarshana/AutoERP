<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('unit_of_measures')->nullOnDelete();
            $table->string('source_line_type');
            $table->unsignedBigInteger('source_line_id');
            $table->decimal('returned_quantity', 20, 6);
            $table->decimal('source_quantity', 20, 6);
            $table->decimal('previously_returned_quantity', 20, 6)->default('0.000000');
            $table->decimal('remaining_quantity', 20, 6);
            $table->decimal('unit_price', 20, 6);
            $table->decimal('cost_basis', 20, 6)->nullable();
            $table->decimal('base_amount', 20, 6)->default('0.000000');
            $table->decimal('discount_amount', 20, 6)->default('0.000000');
            $table->decimal('tax_amount', 20, 6)->default('0.000000');
            $table->decimal('charge_amount', 20, 6)->default('0.000000');
            $table->decimal('line_total', 20, 6);
            $table->foreignId('inventory_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('purchase_return_id', 'purchase_return_lines_return_idx');
            $table->index('item_id', 'purchase_return_lines_item_idx');
            $table->index(['source_line_type', 'source_line_id'], 'purchase_return_lines_source_line_idx');
            $table->index(['purchase_return_id', 'source_line_type', 'source_line_id'], 'purchase_return_lines_return_source_idx');
            $table->index('inventory_movement_id', 'purchase_return_lines_movement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
    }
};
