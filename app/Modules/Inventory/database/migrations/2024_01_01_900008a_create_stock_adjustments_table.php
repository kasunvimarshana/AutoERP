<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->string('reference_number');
            $table->foreignId('warehouse_id')->constrained(null, 'id', 'stock_adjustments_warehouse_id_fk')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations', 'id', 'stock_adjustments_location_id_fk')->nullOnDelete();
            $table->enum('type', ['cycle_count', 'physical_inventory', 'write_off'])->default('cycle_count');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('counted_by')->nullable()->constrained('users', 'id', 'stock_adjustments_counted_by_fk')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id', 'stock_adjustments_approved_by_fk')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'reference_number'], 'stock_adjustments_tenant_ref_uk');
            $table->index(['tenant_id', 'status'], 'stock_adjustments_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
