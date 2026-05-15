<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->foreignId('serial_id')->nullable()->constrained('serials')->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('movement_type');
            $table->nullableMorphs('reference');
            $table->foreignId('uom_id')->constrained('units_of_measure');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'organization_unit_id', 'product_id', 'performed_at'], 'stock_movements_product_performed_at_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'reference_type', 'reference_id'], 'stock_movements_reference_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'from_location_id', 'performed_at'], 'stock_movements_from_location_performed_at_idx');
            $table->index(['tenant_id', 'organization_unit_id', 'to_location_id', 'performed_at'], 'stock_movements_to_location_performed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
