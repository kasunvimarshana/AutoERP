<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_inspections', function (Blueprint $table) {
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

            $table->nullableMorphs('source');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('item_variants')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('transaction_uom_id')->constrained('unit_of_measures');
            $table->foreignId('base_uom_id')->constrained('unit_of_measures');
            $table->decimal('received_quantity', 20, 4)->default(0);
            $table->decimal('base_received_quantity', 20, 4)->default(0);
            $table->decimal('accepted_quantity', 20, 4)->default(0);
            $table->decimal('rejected_quantity', 20, 4)->default(0);
            $table->decimal('damaged_quantity', 20, 4)->default(0);
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('inspection_status')
                ->default('PENDING')
                ->comment('PENDING, IN_PROGRESS, PARTIALLY_ACCEPTED, ACCEPTED, REJECTED, CANCELLED');
            $table->text('notes')->nullable();
            $table->timestamp('inspected_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'source_type', 'source_id'], 'receipt_inspections_source_idx');
            $table->index(['tenant_id', 'item_id', 'inspection_status'], 'receipt_inspections_item_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_inspections');
    }
};
