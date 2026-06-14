<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_state_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('stock_balance_id')->nullable()->constrained('inventory_stock_balances')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('warehouse_location_id')->nullable()->constrained('warehouse_locations')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->restrictOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->restrictOnDelete();
            $table->string('from_state', 40)->nullable();
            $table->string('to_state', 40)->nullable();
            $table->decimal('quantity', 20, 6);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_line_type')->nullable();
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id'], 'inventory_state_changes_scope_idx');
            $table->index(['source_type', 'source_id'], 'inventory_state_changes_source_idx');
            $table->index(['item_id', 'warehouse_id'], 'inventory_state_changes_item_wh_idx');
            $table->index(['from_state', 'to_state'], 'inventory_state_changes_states_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_state_changes');
    }
};
