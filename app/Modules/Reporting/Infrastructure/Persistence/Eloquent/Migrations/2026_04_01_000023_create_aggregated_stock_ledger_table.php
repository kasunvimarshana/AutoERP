<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aggregated_stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')->nullable()->constrained('organization_units', 'id')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_in', 20, 4)->default(0);
            $table->decimal('total_out', 20, 4)->default(0);
            $table->decimal('closing_quantity', 20, 4)->default(0);
            $table->timestamps();
            $table->unique(
                ['tenant_id', 'organization_unit_id', 'product_id', 'warehouse_id', 'period_start', 'period_end'],
                'aggregated_stock_ledger_product_warehouse_period_uk'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregated_stock_ledger');
    }
};
