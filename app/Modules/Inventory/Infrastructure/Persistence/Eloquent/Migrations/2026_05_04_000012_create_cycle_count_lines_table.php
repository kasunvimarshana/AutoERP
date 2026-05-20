<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycle_count_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('count_header_id')->constrained('cycle_count_headers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->decimal('system_qty', 20, 4);
            $table->decimal('counted_qty', 20, 4);
            $table->decimal('variance_qty', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('variance_value', 20, 4);
            $table->foreignId('adjustment_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_count_lines');
    }
};
