<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfer_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('inventory_transfer_id')->constrained('inventory_transfers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('inventory_serial_numbers')->nullOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('dispatched_quantity', 20, 6)->default('0.000000');
            $table->decimal('received_quantity', 20, 6)->default('0.000000');
            $table->decimal('cancelled_quantity', 20, 6)->default('0.000000');
            $table->decimal('unit_cost', 20, 6)->default('0.000000');
            $table->decimal('total_cost', 20, 6)->default('0.000000');
            $table->foreignId('outbound_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->foreignId('inbound_movement_id')->nullable()->constrained('inventory_movements')->nullOnDelete();
            $table->timestamps();

            $table->index('inventory_transfer_id', 'inventory_transfer_lines_transfer_idx');
            $table->index('item_id', 'inventory_transfer_lines_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_lines');
    }
};
