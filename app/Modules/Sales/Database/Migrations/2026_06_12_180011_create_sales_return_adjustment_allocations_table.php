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
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('sales_header_adjustment_id');
            $table->foreign('sales_header_adjustment_id', 'sales_return_adj_alloc_header_fk')
                ->references('id')
                ->on('sales_header_adjustments')
                ->cascadeOnDelete();
            $table->string('adjustment_type');
            $table->string('effect');
            $table->decimal('source_amount', 20, 6);
            $table->decimal('previously_returned_amount', 20, 6)->default('0.000000');
            $table->decimal('returned_amount', 20, 6);
            $table->decimal('remaining_amount', 20, 6);
            $table->timestamps();

            $table->unique(['sales_return_id', 'sales_header_adjustment_id'], 'sales_return_adjustments_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_adjustment_allocations');
    }
};
