<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('inventory_adjustment_id')->constrained('inventory_adjustments')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->decimal('system_quantity', 20, 6)->default('0.000000');
            $table->decimal('counted_quantity', 20, 6)->default('0.000000');
            $table->decimal('adjustment_quantity', 20, 6);
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('total_cost', 20, 6)->default('0.000000');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('inventory_adjustment_id', 'inventory_adjustment_lines_adjustment_idx');
            $table->index('item_id', 'inventory_adjustment_lines_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustment_lines');
    }
};
