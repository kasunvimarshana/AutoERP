<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');

            $table->string('reference_number');
            $table->foreignId('from_location_id')->constrained('warehouse_locations', 'id', 'stock_transfers_from_location_id_fk')->cascadeOnDelete();
            $table->foreignId('to_location_id')->constrained('warehouse_locations', 'id', 'stock_transfers_to_location_id_fk')->cascadeOnDelete();
            $table->enum('status', ['draft', 'pending', 'in_transit', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('requested_by')->constrained('users', 'id', 'stock_transfers_requested_by_fk');
            $table->foreignId('approved_by')->nullable()->constrained('users', 'id', 'stock_transfers_approved_by_fk')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'org_unit_id', 'reference_number'], 'stock_transfers_tenant_ref_uk');
            $table->index(['tenant_id', 'status'], 'stock_transfers_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
