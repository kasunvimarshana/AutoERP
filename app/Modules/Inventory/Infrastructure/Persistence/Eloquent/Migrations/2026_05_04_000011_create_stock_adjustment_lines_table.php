<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete()
                ->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('transaction_uom_id')->constrained('unit_of_measures');
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->string('direction')->default('INCREASE')->comment('INCREASE, DECREASE');
            $table->decimal('current_quantity', 20, 4)->default(0);
            $table->decimal('base_current_quantity', 20, 4)->default(0);
            $table->decimal('adjustment_quantity', 20, 4);
            $table->decimal('base_adjustment_quantity', 20, 4);
            $table->decimal('resulting_quantity', 20, 4);
            $table->decimal('base_resulting_quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->string('reason_code')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('variance_value', 20, 4)->nullable();
            $table->foreignId('adjustment_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_lines');
    }
};
