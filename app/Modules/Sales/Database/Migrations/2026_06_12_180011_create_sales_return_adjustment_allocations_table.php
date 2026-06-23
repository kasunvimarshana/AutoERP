<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_return_adjustment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable();
            $table->foreignId('sales_return_id');
            $table->foreignId('sales_header_adjustment_id');
            $table->string('adjustment_type');
            $table->string('effect');
            $table->decimal('source_amount', 20, 6);
            $table->decimal('previously_returned_amount', 20, 6)->default('0.000000');
            $table->decimal('returned_amount', 20, 6);
            $table->decimal('remaining_amount', 20, 6);
            $table->timestamps();

            $table->unique(['sales_return_id', 'sales_header_adjustment_id'], 'sales_return_adjustments_uk');

            $table->unique(['id', 'tenant_id'], 'sales_return_adjustment_allocations_id_tenant_uk');
            $table->foreign(['organization_unit_id', 'tenant_id'], 'sales_return_adjustment_allocations_organization_c6eb7e6c_fk')
                ->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->restrictOnDelete();
            $table->foreign(['sales_return_id', 'tenant_id'], 'sales_return_adjustment_allocations_sales_return_6f15c87a_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_returns')
                ->cascadeOnDelete();
            $table->foreign(['sales_header_adjustment_id', 'tenant_id'], 'sales_return_adj_alloc_header_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('sales_header_adjustments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_adjustment_allocations');
    }
};
