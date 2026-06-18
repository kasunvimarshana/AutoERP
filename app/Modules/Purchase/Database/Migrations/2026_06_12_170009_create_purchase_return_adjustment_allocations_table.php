<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_return_adjustment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreign('organization_unit_id', 'purchase_return_adj_alloc_org_fk')
                ->references('id')
                ->on('organization_units')
                ->nullOnDelete();
            $table->foreignId('purchase_return_id');
            $table->foreign('purchase_return_id', 'purchase_return_adj_alloc_return_fk')
                ->references('id')
                ->on('purchase_returns')
                ->cascadeOnDelete();
            $table->foreignId('purchase_header_adjustment_id');
            $table->foreign('purchase_header_adjustment_id', 'purchase_return_adj_alloc_header_fk')
                ->references('id')
                ->on('purchase_header_adjustments')
                ->cascadeOnDelete();
            $table->string('adjustment_type');
            $table->string('effect');
            $table->decimal('source_amount', 20, 6);
            $table->decimal('previously_returned_amount', 20, 6)->default('0.000000');
            $table->decimal('returned_amount', 20, 6);
            $table->decimal('remaining_amount', 20, 6);
            $table->timestamps();

            $table->index('purchase_return_id', 'purchase_return_adjustment_allocations_return_idx');
            $table->index('purchase_header_adjustment_id', 'purchase_return_adjustment_allocations_adjustment_idx');
            $table->unique(['purchase_return_id', 'purchase_header_adjustment_id'], 'purchase_return_adj_alloc_return_header_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_adjustment_allocations');
    }
};
